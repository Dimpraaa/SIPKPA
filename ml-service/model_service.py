import logging
from datetime import datetime, timezone
from pathlib import Path
from tempfile import NamedTemporaryFile

import joblib
import numpy as np
import pandas as pd
from fastapi import HTTPException
from sklearn.ensemble import RandomForestClassifier
from sklearn.metrics import (
    accuracy_score,
    confusion_matrix,
    f1_score,
    precision_score,
    recall_score,
)
from sklearn.model_selection import train_test_split
from sklearn.utils.validation import check_is_fitted

from schemas import (
    FEATURE_NAMES,
    ConfusionMatrix,
    HealthResponse,
    ParticipantFeatures,
    ParticipantPrediction,
    PredictRequest,
    PredictResponse,
    TrainRequest,
    TrainResponse,
)

logger = logging.getLogger(__name__)


def generate_recommendation(features: ParticipantFeatures) -> str:
    recommendations = []
    if features.persen_kehadiran < 80:
        recommendations.append("Ikuti pembinaan disiplin kehadiran (standar minimal 80%).")
    if features.nilai_kode_etik < 70:
        recommendations.append("Ikuti pendalaman dan remedial Kode Etik (standar minimal 70).")
    if features.nilai_hukum_acara < 70:
        recommendations.append("Ikuti kelas klinik remedial Hukum Acara (standar minimal 70).")
    if features.nilai_tugas_praktik < 70:
        recommendations.append("Perbaiki Tugas Praktik melalui bimbingan penyusunan dokumen hukum (standar minimal 70).")
    if features.nilai_keaktifan < 70:
        recommendations.append("Tingkatkan Keaktifan dalam diskusi dan latihan kasus (standar minimal 70).")
    if features.nilai_simulasi_upa < 70:
        recommendations.append("Ikuti latihan dan pembahasan Simulasi UPA secara terarah (standar minimal 70).")
    return " ".join(recommendations) if recommendations else "Pertahankan performa dan siap mengikuti UPA"


class ModelService:
    def __init__(self, model_directory: Path) -> None:
        self.model_directory = model_directory.resolve()

    def train(self, payload: TrainRequest) -> TrainResponse:
        frame = pd.DataFrame([row.model_dump() for row in payload.data])
        features = frame.loc[:, list(FEATURE_NAMES)]
        labels = frame["status_aktual"].map({"Tidak Lulus": 0, "Lulus": 1})
        train_features, test_features, train_labels, test_labels = train_test_split(
            features,
            labels,
            test_size=payload.test_size,
            random_state=payload.random_state,
            stratify=labels,
        )
        if train_labels.nunique() != 2 or test_labels.nunique() != 2:
            raise HTTPException(
                status_code=422,
                detail="Pembagian stratified harus menyisakan kedua kelas pada data latih "
                "dan data uji. Tambahkan data kelas minoritas atau sesuaikan test_size.",
            )

        model = RandomForestClassifier(
            n_estimators=payload.n_estimators,
            max_depth=payload.max_depth,
            criterion=payload.criterion,
            random_state=payload.random_state,
            n_jobs=-1,
        )
        model.fit(train_features, train_labels)
        predicted_labels = model.predict(test_features)
        tn, fp, fn, tp = confusion_matrix(test_labels, predicted_labels, labels=[0, 1]).ravel()

        return TrainResponse(
            jumlah_data_latih=len(train_features),
            jumlah_data_uji=len(test_features),
            n_estimators=payload.n_estimators,
            max_depth=payload.max_depth,
            criterion=payload.criterion,
            test_size=payload.test_size,
            random_state=payload.random_state,
            akurasi=round(float(accuracy_score(test_labels, predicted_labels)) * 100, 2),
            presisi=round(float(precision_score(test_labels, predicted_labels, pos_label=1, zero_division=0)) * 100, 2),
            recall=round(float(recall_score(test_labels, predicted_labels, pos_label=1, zero_division=0)) * 100, 2),
            f1_score=round(float(f1_score(test_labels, predicted_labels, pos_label=1, zero_division=0)) * 100, 2),
            confusion_matrix=ConfusionMatrix(TP=int(tp), TN=int(tn), FP=int(fp), FN=int(fn)),
            feature_importance={
                feature: float(importance)
                for feature, importance in zip(FEATURE_NAMES, model.feature_importances_)
            },
            file_path_model=self.save_model(model),
        )

    def save_model(self, model: RandomForestClassifier) -> str:
        temporary_path = None
        try:
            self.model_directory.mkdir(parents=True, exist_ok=True)
            timestamp = datetime.now(timezone.utc).strftime("%Y%m%d_%H%M%S_%f")
            model_path = self.model_directory / f"rf_model_{timestamp}.pkl"
            with NamedTemporaryFile(dir=self.model_directory, suffix=".tmp", delete=False) as temporary:
                temporary_path = Path(temporary.name)
            joblib.dump(model, temporary_path)
            temporary_path.replace(model_path)
        except OSError as error:
            logger.exception("Gagal menyimpan model Random Forest.")
            raise HTTPException(status_code=500, detail="Model gagal disimpan pada penyimpanan layanan.") from error
        finally:
            if temporary_path is not None:
                temporary_path.unlink(missing_ok=True)
        return self.public_path(model_path)

    def public_path(self, model_path: Path) -> str:
        return f"models/{model_path.name}"

    def resolve_model_path(self, requested_path: str | None) -> Path:
        if requested_path is None:
            candidates = sorted(self.model_directory.glob("rf_model_*.pkl"))
            if not candidates:
                raise HTTPException(status_code=503, detail="Belum ada model terlatih. Jalankan pelatihan terlebih dahulu.")
            candidate = candidates[-1]
        else:
            candidate = Path(requested_path)
            if not candidate.is_absolute():
                if candidate.parent not in (Path("."), Path("models")):
                    raise HTTPException(status_code=400, detail="Path model harus merujuk ke folder models layanan.")
                candidate = self.model_directory / candidate.name

        try:
            candidate = candidate.resolve()
        except (OSError, ValueError, RuntimeError) as error:
            raise HTTPException(status_code=400, detail="Path berkas model tidak valid.") from error
        if (
            candidate.parent != self.model_directory
            or candidate.suffix != ".pkl"
            or not candidate.name.startswith("rf_model_")
        ):
            raise HTTPException(status_code=400, detail="Path model harus berupa berkas rf_model_*.pkl di folder models layanan.")
        if not candidate.is_file():
            raise HTTPException(status_code=404, detail="Berkas model yang diminta tidak ditemukan.")
        return candidate

    def load_model(self, requested_path: str | None) -> tuple[RandomForestClassifier, Path]:
        model_path = self.resolve_model_path(requested_path)
        try:
            model = joblib.load(model_path)
            if not isinstance(model, RandomForestClassifier):
                raise ValueError("Jenis model bukan Random Forest.")
            check_is_fitted(model)
            if list(model.feature_names_in_) != list(FEATURE_NAMES) or set(model.classes_) != {0, 1}:
                raise ValueError("Fitur atau kelas model tidak sesuai kontrak layanan.")
        except Exception as error:
            logger.exception("Model Random Forest tidak dapat dimuat.")
            raise HTTPException(status_code=503, detail="Berkas model rusak atau tidak sesuai. Latih ulang model.") from error
        return model, model_path

    def predict(self, payload: PredictRequest) -> PredictResponse:
        model, model_path = self.load_model(payload.file_path_model)
        features = pd.DataFrame([row.model_dump() for row in payload.data], columns=list(FEATURE_NAMES))
        predicted_labels = model.predict(features)
        probabilities = model.predict_proba(features)
        pass_index = int(np.flatnonzero(model.classes_ == 1)[0])
        fail_index = int(np.flatnonzero(model.classes_ == 0)[0])

        results = [
            ParticipantPrediction(
                status_prediksi="Lulus" if label == 1 else "Berisiko Tidak Lulus",
                probabilitas_lulus=round(float(probability[pass_index]) * 100, 2),
                probabilitas_gagal=round(float(probability[fail_index]) * 100, 2),
                rekomendasi_tindak_lanjut=generate_recommendation(row),
            )
            for row, label, probability in zip(payload.data, predicted_labels, probabilities)
        ]
        return PredictResponse(file_path_model=self.public_path(model_path), hasil_prediksi=results)

    def health(self) -> HealthResponse:
        try:
            _, model_path = self.load_model(None)
        except HTTPException as error:
            no_model = not any(self.model_directory.glob("rf_model_*.pkl"))
            return HealthResponse(
                status="ok" if no_model else "degraded",
                model_aktif=False,
                file_path_model=None,
                pesan=str(error.detail),
            )
        return HealthResponse(
            status="ok",
            model_aktif=True,
            file_path_model=self.public_path(model_path),
            pesan="Layanan siap dan model terbaru dapat digunakan untuk prediksi.",
        )
