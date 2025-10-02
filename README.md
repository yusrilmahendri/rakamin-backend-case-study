📘 **AI-Powered CV & Project Evaluator**
AI-Powered Candidate Screening Backend
A backend service to evaluate candidate CVs and project reports against a job description and case study brief using AI/LLM + RAG pipeline.

**Candidate Information**
Full Name: Yusril Mahendri, S.Kom.
Email: yusrilmahendri.yusril@gmail.com

**Approach & Design**
    Endpoints:
        /v1/register → Endpoint untuk mendaftar user baru.
        /v1//login → Endpoint untuk login user.
        /v1/logout → Logout user, token dihapus.
        /v1/upload → Upload file atau data untuk dievaluasi (misal dokumen).
        /v1/evaluate → Menjalankan proses evaluasi berdasarkan data yang diupload.
        /v1/status/{id} → Mengecek status evaluasi berdasarkan id.
        /v1/result/{id} → Mengambil hasil evaluasi berdasarkan id.
    Database Schema:
        documents → menyimpan file CV, Report, JobDesc, Rubric.
        jobs → menyimpan status evaluasi (queued, processing, completed, failed).
        results → menyimpan hasil evaluasi (skor + feedback).
        
**Job Queue**
    Gunakan Laravel Queue + Redis untuk eksekusi asynchronous.
    Worker memproses parsing PDF, memanggil API LLM, dan menyimpan hasil evaluasi.
    
**LLM Integration**
    Provider: OpenAI (gpt-4o-mini).
    Prompt Design:
        CV Evaluation → menilai skills, experience, achievements, cultural fit.
        Project Evaluation → menilai correctness, code quality, resilience, documentation.
        Final Analysis → gabungan CV + Project menjadi overall summary.
        Temperature: 0.2 untuk menjaga konsistensi.
        
**RAG Strategy**
    Versi awal (MVP): gunakan teks dari Job Description + Case Study Brief yang sudah di-ingest manual.
    Future: integrasi dengan vector DB (ChromaDB/Qdrant) untuk retrieval otomatis.
    
**Error Handling**
    Gunakan try/catch pada job.
    Jika LLM gagal (timeout/rate limit) → retry dengan backoff.
    Status job diupdate → failed bila tidak berhasil.
    
**Edge Cases**
    File PDF kosong/korup.
    API LLM timeout.
    Kandidat tanpa pengalaman relevan → evaluasi tetap jalan dengan feedback default.

🔑 **Authentication (Sanctum)**
   ``` POST /api/register
    {
      "name": "User",
      "email": "user@example.com",
      "password": "secret123",
      "password_confirmation": "secret123"
    }
    **LOGIN**
    POST /api/login
    {
      "email": "user@example.com",
      "password": "secret123"
    }
    Response akan mengembalikan token:
    {
      "user": { "id": 1, "name": "User", "email": "user@example.com" },
      "token": "1|abcdefg..."
    }```
    
📌 **API Endpoints**
   ``` Upload CV & Report
        POST /api/upload
        Content-Type: multipart/form-data
        Authorization: Bearer <token>
        cv: file.pdf
        report: file.pdf
    
    response 
        { "cv_id": 1, "report_id": 2 }
        
    Evaluate
        POST /api/evaluate
        Authorization: Bearer <token>

        response: 
        {
            "id": 2,
            "status": "queued"
        }

    Check Status
        GET /api/status/{jobId}
        Authorization: Bearer <token>
       
        response:
        {
            "id": 1,
            "status": "completed"
        }

    Get Result
        GET /api/result/{jobId}
        Authorization: Bearer <token>
        
        response:
        {
            "id": 1,
            "status": "completed",
            "result": {
                "id": 1,
                "job_id": 1,
                "cv_match_rate": 0.78,
                "cv_feedback": "No feedback",
                "project_score": 3,
                "project_feedback": "No feedback",
                "overall_summary": "Candidate has strong backend skills with some AI exposure. Could improve resilience.",
                "created_at": "2025-10-02T18:56:18.000000Z",
                "updated_at": "2025-10-02T18:56:18.000000Z"
            }
        }```

**testing**
Gunakan Postman atau cURL untuk mencoba semua endpoint.
Atau jalankan queue worker:
    ```php artisan queue:work```

 📂 **Project Structure**
 ```app/
 ├── Http/
 │   └── Controllers/
 │       └── EvaluationController.php
 │       └── AuthController.php
 ├── Jobs/
 │   └── ProcessEvaluation.php
 ├── Models/
 │   └── Document.php
 │   └── Job.php
 │   └── Result.php
```

## 🚀 Tech Stack
- [Laravel 10](https://laravel.com/) - PHP Framework
- [MySQL](https://www.mysql.com/) - Database
- [Laravel Sanctum](https://laravel.com/docs/10.x/sanctum) - API Authentication
- [Smalot/pdfparser](https://github.com/smalot/pdfparser) - PDF Parsing
- [OpenAI API](https://platform.openai.com/) - LLM Integration
- Queue (Sync/Database/Redis)

## ⚙️ Setup Project
1. **Clone Repository**
   ```git clone https://github.com/yusrilmahendri/rakamin-backend-case-study.git
   cd rakamin-backend-case-study```

2. **Install Dependencies**
    ```composer install```
    
3. **Setup Environment**
    Copy .env.example ke .env lalu sesuaikan konfigurasi:
       cp .env.example .env
    Database config (DB_DATABASE, DB_USERNAME, DB_PASSWORD)
    OpenAI API Key:
        ```OPENAI_API_KEY=your_api_key_here```
4. **Generate App Key**
    ```php artisan key:generate```
5. **Migrate Database**
    php artisan migrate


