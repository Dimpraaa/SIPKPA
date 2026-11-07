from pathlib import Path

from fastapi import FastAPI, Request
from fastapi.exceptions import RequestValidationError
from fastapi.responses import JSONResponse

from model_service import ModelService
from schemas import HealthResponse, PredictRequest, PredictResponse, TrainRequest, TrainResponse


def create_app(model_directory: Path | None = None) -> FastAPI:
    application = FastAPI(
        title="Layanan Machine Learning SIPKPA-PROPINDO",
        description="Prediksi kelulusan PKPA menggunakan enam fitur penilaian. "
        "Metrik dan probabilitas berskala persen; kelas positif adalah Lulus. "
        "Feature importance berupa proporsi 0–1. Model aktif adalah model terbaru.",
        version="0.2.0",
    )
    service = ModelService(model_directory if model_directory is not None else Path(__file__).parent / "models")

    @application.exception_handler(RequestValidationError)
    async def validation_error_handler(request: Request, exception: RequestValidationError) -> JSONResponse:
        messages = {
            "missing": "Kolom ini wajib diisi.",
            "extra_forbidden": "Kolom ini tidak dikenali.",
            "float_parsing": "Nilai harus berupa angka.",
            "float_type": "Nilai harus berupa angka.",
            "int_type": "Nilai harus berupa bilangan bulat.",
            "finite_number": "Nilai harus berupa angka terhingga.",
            "literal_error": "Pilihan nilai tidak sesuai dengan opsi yang diizinkan.",
            "list_type": "Data harus berupa array.",
            "string_type": "Nilai harus berupa teks.",
            "json_invalid": "Format JSON tidak valid.",
            "model_attributes_type": "Data harus berupa objek JSON.",
        }
        details = []
        for error in exception.errors():
            kind = error["type"]
            context = error.get("ctx", {})
            message = messages.get(kind, "Nilai tidak valid untuk kolom ini.")
            if kind == "value_error":
                message = str(context.get("error", message))
            elif kind == "too_short":
                message = f"Data harus memuat sedikitnya {context['min_length']} entri."
            elif kind in ("greater_than", "greater_than_equal", "less_than", "less_than_equal"):
                descriptions = {
                    "greater_than": "lebih besar dari",
                    "greater_than_equal": "minimal",
                    "less_than": "lebih kecil dari",
                    "less_than_equal": "maksimal",
                }
                bound = context.get("gt", context.get("ge", context.get("lt", context.get("le"))))
                message = f"Nilai harus {descriptions[kind]} {bound}."
            details.append({"lokasi": list(error["loc"]), "pesan": message})
        return JSONResponse(status_code=422, content={"detail": details})

    @application.get("/health", response_model=HealthResponse, summary="Periksa kesehatan layanan dan model terbaru")
    def health_check() -> HealthResponse:
        return service.health()

    @application.post("/api/train", response_model=TrainResponse, summary="Latih dan evaluasi model Random Forest")
    def train_model(payload: TrainRequest) -> TrainResponse:
        return service.train(payload)

    @application.post("/api/predict", response_model=PredictResponse, summary="Prediksi kelulusan dan rekomendasi peserta")
    def predict_participants(payload: PredictRequest) -> PredictResponse:
        return service.predict(payload)

    return application


app = create_app()
