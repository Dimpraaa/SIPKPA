from collections import Counter
from math import ceil
from typing import Annotated, Any, Literal

from pydantic import BaseModel, ConfigDict, Field, field_validator, model_validator

FEATURE_NAMES = (
    "persen_kehadiran",
    "nilai_kode_etik",
    "nilai_hukum_acara",
    "nilai_tugas_praktik",
    "nilai_keaktifan",
    "nilai_simulasi_upa",
)

Score = Annotated[float, Field(ge=0, le=100, allow_inf_nan=False)]


class ParticipantFeatures(BaseModel):
    model_config = ConfigDict(extra="forbid")

    persen_kehadiran: Score
    nilai_kode_etik: Score
    nilai_hukum_acara: Score
    nilai_tugas_praktik: Score
    nilai_keaktifan: Score
    nilai_simulasi_upa: Score

    @field_validator(*FEATURE_NAMES, mode="before")
    @classmethod
    def reject_boolean_scores(cls, value: Any) -> Any:
        if isinstance(value, bool):
            raise ValueError("Nilai fitur harus berupa angka, bukan boolean.")
        return value


class HistoricalParticipant(ParticipantFeatures):
    status_aktual: Literal["Lulus", "Tidak Lulus"]


class TrainRequest(BaseModel):
    model_config = ConfigDict(extra="forbid")

    data: list[HistoricalParticipant] = Field(min_length=4)
    n_estimators: int = Field(default=100, gt=0, strict=True)
    max_depth: int | None = Field(default=None, gt=0, strict=True)
    criterion: Literal["gini", "entropy"] = "gini"
    test_size: float = Field(default=0.2, gt=0, lt=1, strict=True)
    random_state: int = Field(default=42, ge=0, le=4294967295, strict=True)

    @model_validator(mode="after")
    def validate_stratified_split(self) -> "TrainRequest":
        counts = Counter(row.status_aktual for row in self.data)
        if len(counts) != 2:
            raise ValueError("Data historis harus memuat kelas Lulus dan Tidak Lulus.")
        if min(counts.values()) < 2:
            raise ValueError("Setiap kelas harus memiliki sedikitnya dua peserta.")

        test_count = ceil(len(self.data) * self.test_size)
        if min(test_count, len(self.data) - test_count) < 2:
            raise ValueError(
                "Ukuran data latih dan data uji masing-masing harus sedikitnya dua "
                "agar kedua kelas dapat dibagi secara stratified."
            )
        return self


class PredictRequest(BaseModel):
    model_config = ConfigDict(extra="forbid")

    file_path_model: str | None = None
    data: list[ParticipantFeatures] = Field(min_length=1)

    @field_validator("file_path_model")
    @classmethod
    def normalize_model_path(cls, value: str | None) -> str | None:
        if value is None:
            return None
        return value.strip() or None


class ConfusionMatrix(BaseModel):
    TP: int
    TN: int
    FP: int
    FN: int


class TrainResponse(BaseModel):
    jumlah_data_latih: int
    jumlah_data_uji: int
    n_estimators: int
    max_depth: int | None
    criterion: str
    test_size: float
    random_state: int
    akurasi: float
    presisi: float
    recall: float
    f1_score: float
    confusion_matrix: ConfusionMatrix
    feature_importance: dict[str, float]
    file_path_model: str


class ParticipantPrediction(BaseModel):
    status_prediksi: Literal["Lulus", "Berisiko Tidak Lulus"]
    probabilitas_lulus: float
    probabilitas_gagal: float
    rekomendasi_tindak_lanjut: str


class PredictResponse(BaseModel):
    file_path_model: str
    hasil_prediksi: list[ParticipantPrediction]


class HealthResponse(BaseModel):
    status: Literal["ok", "degraded"]
    layanan: str = "SIPKPA-PROPINDO ML"
    model_aktif: bool
    file_path_model: str | None
    pesan: str
