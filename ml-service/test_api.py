import json
import random
import socket
import tempfile
import threading
import time
import unittest
from contextlib import contextmanager
from pathlib import Path
from typing import Any, Iterator
from urllib.error import HTTPError
from urllib.request import Request, urlopen

import joblib
import numpy as np
import pandas as pd
import uvicorn
from sklearn.ensemble import RandomForestClassifier
from sklearn.model_selection import train_test_split

from main import create_app
from model_service import generate_recommendation
from schemas import FEATURE_NAMES, ParticipantFeatures


@contextmanager
def running_server(model_directory: Path) -> Iterator[str]:
    connection = socket.socket()
    connection.bind(("127.0.0.1", 0))
    server = uvicorn.Server(
        uvicorn.Config(create_app(model_directory), log_level="error", lifespan="off")
    )
    thread = threading.Thread(target=server.run, kwargs={"sockets": [connection]}, daemon=True)
    thread.start()
    try:
        deadline = time.monotonic() + 15
        while not server.started:
            if not thread.is_alive() or time.monotonic() >= deadline:
                raise RuntimeError("Server pengujian gagal dimulai.")
            time.sleep(0.01)
        yield f"http://127.0.0.1:{connection.getsockname()[1]}"
    finally:
        server.should_exit = True
        thread.join(timeout=15)
        connection.close()


def request_json(base_url: str, path: str, payload: dict[str, Any] | None = None) -> tuple[int, dict[str, Any]]:
    request = Request(
        base_url + path,
        data=json.dumps(payload).encode() if payload is not None else None,
        headers={"Content-Type": "application/json"},
    )
    try:
        with urlopen(request, timeout=30) as response:
            return response.status, json.load(response)
    except HTTPError as error:
        return error.code, json.load(error)


def historical_data() -> list[dict[str, Any]]:
    generator = random.Random(20261107)
    data = []
    for index in range(200):
        ability = generator.uniform(58, 92)
        features = {
            feature: round(max(0, min(100, ability + generator.uniform(-12, 12))), 2)
            for feature in FEATURE_NAMES
        }
        features["persen_kehadiran"] = round(min(100, features["persen_kehadiran"] + 12), 2)
        passed = (
            features["persen_kehadiran"] >= 80
            and features["nilai_hukum_acara"] >= 70
            and features["nilai_tugas_praktik"] >= 70
        )
        if index % 13 == 0:
            passed = not passed
        data.append({**features, "status_aktual": "Lulus" if passed else "Tidak Lulus"})
    return data


class RandomForestApiTest(unittest.TestCase):
    def test_training_metrics_probabilities_and_latest_model_survive_restart(self) -> None:
        data = historical_data()
        payload = {
            "data": data,
            "n_estimators": 40,
            "max_depth": 6,
            "criterion": "entropy",
        }
        with tempfile.TemporaryDirectory() as directory:
            model_directory = Path(directory) / "models"
            with running_server(model_directory) as base_url:
                status, health = request_json(base_url, "/health")
                self.assertEqual(status, 200)
                self.assertFalse(health["model_aktif"])
                self.assertEqual(request_json(base_url, "/api/predict", {"data": [{feature: 80 for feature in FEATURE_NAMES}]})[0], 503)

                status, trained = request_json(base_url, "/api/train", payload)
                self.assertEqual(status, 200, trained)
                self.assertEqual((trained["jumlah_data_latih"], trained["jumlah_data_uji"]), (160, 40))
                self.assertEqual(trained["random_state"], 42)
                self.assertEqual(trained["test_size"], 0.2)
                self.assertRegex(trained["file_path_model"], r"^models/rf_model_\d{8}_\d{6}_\d{6}\.pkl$")
                self.assertEqual(len(list(model_directory.glob("*.tmp"))), 0)

                frame = pd.DataFrame(data)
                features = frame.loc[:, list(FEATURE_NAMES)]
                labels = frame["status_aktual"].map({"Tidak Lulus": 0, "Lulus": 1})
                x_train, x_test, y_train, y_test = train_test_split(features, labels, stratify=labels, test_size=0.2, random_state=42)
                loaded = joblib.load(model_directory / Path(trained["file_path_model"]).name)
                self.assertEqual(loaded.get_params()["criterion"], "entropy")
                self.assertEqual(loaded.get_params()["max_depth"], 6)
                self.assertEqual(loaded.get_params()["n_estimators"], 40)
                reference = RandomForestClassifier(n_estimators=40, max_depth=6, criterion="entropy", random_state=42)
                reference.fit(x_train, y_train)
                actual_labels = loaded.predict(x_test)
                np.testing.assert_array_equal(actual_labels, reference.predict(x_test))

                pairs = list(zip(y_test, actual_labels))
                tp = sum(actual == 1 and predicted == 1 for actual, predicted in pairs)
                tn = sum(actual == 0 and predicted == 0 for actual, predicted in pairs)
                fp = sum(actual == 0 and predicted == 1 for actual, predicted in pairs)
                fn = sum(actual == 1 and predicted == 0 for actual, predicted in pairs)
                self.assertGreater(tp, 0)
                self.assertGreater(tn, 0)
                self.assertGreater(fp + fn, 0)
                self.assertEqual(trained["confusion_matrix"], {"TP": tp, "TN": tn, "FP": fp, "FN": fn})
                self.assertEqual(trained["akurasi"], round(100 * (tp + tn) / 40, 2))
                self.assertEqual(trained["presisi"], round(100 * tp / (tp + fp), 2))
                self.assertEqual(trained["recall"], round(100 * tp / (tp + fn), 2))
                self.assertEqual(trained["f1_score"], round(100 * 2 * tp / (2 * tp + fp + fn), 2))
                self.assertEqual(set(trained["feature_importance"]), set(FEATURE_NAMES))
                self.assertAlmostEqual(sum(trained["feature_importance"].values()), 1.0)
                np.testing.assert_allclose(list(trained["feature_importance"].values()), loaded.feature_importances_)

                good = {feature: 95 for feature in reversed(FEATURE_NAMES)}
                poor = {feature: 45 for feature in FEATURE_NAMES}
                batch = [poor, good]
                status, predicted = request_json(base_url, "/api/predict", {"file_path_model": trained["file_path_model"], "data": batch})
                self.assertEqual(status, 200, predicted)
                self.assertEqual(len(predicted["hasil_prediksi"]), 2)
                matrix = pd.DataFrame(batch, columns=list(FEATURE_NAMES))
                probabilities = reference.predict_proba(matrix)
                for result, label, probability in zip(predicted["hasil_prediksi"], reference.predict(matrix), probabilities):
                    self.assertEqual(result["status_prediksi"], "Lulus" if label == 1 else "Berisiko Tidak Lulus")
                    self.assertEqual(result["probabilitas_lulus"], round(float(probability[list(reference.classes_).index(1)]) * 100, 2))
                    self.assertEqual(result["probabilitas_gagal"], round(float(probability[list(reference.classes_).index(0)]) * 100, 2))
                    self.assertAlmostEqual(result["probabilitas_lulus"] + result["probabilitas_gagal"], 100, places=2)
                self.assertEqual(predicted["hasil_prediksi"][1]["rekomendasi_tindak_lanjut"], "Pertahankan performa dan siap mengikuti UPA")
                for component in ("kehadiran", "Kode Etik", "Hukum Acara", "Tugas Praktik", "Keaktifan", "Simulasi UPA"):
                    self.assertIn(component, predicted["hasil_prediksi"][0]["rekomendasi_tindak_lanjut"])

                status, second = request_json(base_url, "/api/train", {"data": data, "n_estimators": 10, "test_size": 0.3, "random_state": 7})
                self.assertEqual(status, 200, second)
                self.assertEqual(second["criterion"], "gini")
                default_model = joblib.load(model_directory / Path(second["file_path_model"]).name)
                self.assertEqual(default_model.get_params()["criterion"], "gini")
                self.assertEqual((second["jumlah_data_latih"], second["jumlah_data_uji"]), (140, 60))
                self.assertNotEqual(trained["file_path_model"], second["file_path_model"])
                self.assertEqual(request_json(base_url, "/health")[1]["file_path_model"], second["file_path_model"])
                self.assertEqual(request_json(base_url, "/api/predict", {"data": batch})[1]["file_path_model"], second["file_path_model"])
                self.assertEqual(request_json(base_url, "/api/predict", {"data": batch, "file_path_model": trained["file_path_model"]})[1], predicted)

            with running_server(model_directory) as base_url:
                self.assertTrue(request_json(base_url, "/health")[1]["model_aktif"])
                status, latest = request_json(base_url, "/api/predict", {"file_path_model": "  ", "data": batch})
                self.assertEqual(status, 200, latest)
                self.assertEqual(latest["file_path_model"], second["file_path_model"])

    def test_invalid_training_and_prediction_payloads_return_422_without_saving_models(self) -> None:
        data = historical_data()
        good = {feature: 80 for feature in FEATURE_NAMES}
        invalid_training = [
            {"data": []},
            {"data": [{**row, "status_aktual": "Lulus"} for row in data]},
            {"data": [{**row, "status_aktual": "Lulus" if index == 0 else "Tidak Lulus"} for index, row in enumerate(data)]},
            {"data": [{**data[0], "status_aktual": "Belum Ditentukan"}, *data[1:]]},
            {"data": data, "n_estimators": 0},
            {"data": data, "n_estimators": True},
            {"data": data, "max_depth": -1},
            {"data": data, "criterion": "log_loss"},
            {"data": data, "test_size": 0},
            {"data": data, "test_size": 1},
            {"data": data, "test_size": 0.001},
            {"data": data, "random_state": -1},
            {"data": data, "random_state": 4294967296},
            {"data": [*data[:2], *data[2:4]], "test_size": 0.2},
        ]
        invalid_prediction = [
            {"data": []},
            {"data": [{**good, "persen_kehadiran": -1}]},
            {"data": [{**good, "nilai_hukum_acara": 101}]},
            {"data": [{**good, "nilai_kode_etik": "NaN"}]},
            {"data": [{**good, "nilai_kode_etik": float("nan")}]},
            {"data": [{**good, "nilai_simulasi_upa": "Infinity"}]},
            {"data": [{**good, "nilai_keaktifan": True}]},
            {"data": [{key: value for key, value in good.items() if key != "nilai_tugas_praktik"}]},
        ]
        with tempfile.TemporaryDirectory() as directory:
            models = Path(directory) / "models"
            with running_server(models) as base_url:
                for endpoint, cases in (("/api/train", invalid_training), ("/api/predict", invalid_prediction)):
                    for payload in cases:
                        with self.subTest(endpoint=endpoint, case=cases.index(payload)):
                            status, result = request_json(base_url, endpoint, payload)
                            self.assertEqual(status, 422, result)
                            self.assertIn("pesan", result["detail"][0])
                self.assertFalse(models.exists())

    def test_missing_unsafe_and_corrupt_models_return_actionable_errors(self) -> None:
        good = {feature: 80 for feature in FEATURE_NAMES}
        with tempfile.TemporaryDirectory() as directory:
            models = Path(directory) / "models"
            models.mkdir()
            corrupt = models / "rf_model_20261107_120000_000001.pkl"
            corrupt.write_bytes(b"model tidak valid")
            with running_server(models) as base_url:
                for path, expected in (("models/rf_model_missing.pkl", 404), ("../outside.pkl", 400), ("models/model.txt", 400), ("models/rf_model_\x00.pkl", 400), (str(Path(directory) / "outside.pkl"), 400)):
                    with self.subTest(path=path):
                        self.assertEqual(request_json(base_url, "/api/predict", {"file_path_model": path, "data": [good]})[0], expected)
                with self.assertLogs("model_service", level="ERROR"):
                    self.assertEqual(request_json(base_url, "/api/predict", {"data": [good]})[0], 503)
                    status, health = request_json(base_url, "/health")
                self.assertEqual(status, 200)
                self.assertEqual(health["status"], "degraded")
                self.assertFalse(health["model_aktif"])

    def test_metrics_remain_defined_when_model_predicts_only_the_negative_class(self) -> None:
        features = {feature: 70 for feature in FEATURE_NAMES}
        data = [
            {**features, "status_aktual": "Tidak Lulus" if index < 80 else "Lulus"}
            for index in range(100)
        ]
        with tempfile.TemporaryDirectory() as directory:
            with running_server(Path(directory) / "models") as base_url:
                status, result = request_json(base_url, "/api/train", {"data": data, "n_estimators": 10})
                self.assertEqual(status, 200, result)
                self.assertEqual(result["akurasi"], 80.0)
                self.assertEqual(result["presisi"], 0.0)
                self.assertEqual(result["recall"], 0.0)
                self.assertEqual(result["f1_score"], 0.0)
                self.assertEqual(result["confusion_matrix"], {"TP": 0, "TN": 16, "FP": 0, "FN": 4})

    def test_recommendations_identify_each_component_and_respect_exact_thresholds(self) -> None:
        baseline = {feature: 70 for feature in FEATURE_NAMES}
        baseline["persen_kehadiran"] = 80
        self.assertEqual(generate_recommendation(ParticipantFeatures(**baseline)), "Pertahankan performa dan siap mengikuti UPA")
        for feature, component in zip(FEATURE_NAMES, ("kehadiran", "Kode Etik", "Hukum Acara", "Tugas Praktik", "Keaktifan", "Simulasi UPA")):
            with self.subTest(feature=feature):
                result = generate_recommendation(ParticipantFeatures(**{**baseline, feature: baseline[feature] - 0.01}))
                self.assertIn(component, result)
                self.assertEqual(result.count("."), 1)


if __name__ == "__main__":
    unittest.main(verbosity=2)
