<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil & Manajeman API Key</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
    <style>
        :root {
            --primary-color: #4F46E5; /* Indigo */
        }
        body { 
            background-color: #f0f2f5; 
            font-family: 'Poppins', sans-serif; 
            padding-top: 70px; 
        }
        .navbar { 
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05); 
        }
        .card { 
            border-radius: 12px; 
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08); 
            border: 1px solid #e0e0e0;
            margin-bottom: 25px;
        }
        .profile-header {
            background-color: var(--primary-color);
            color: white;
            padding: 30px 20px;
            border-top-left-radius: 12px;
            border-top-right-radius: 12px;
        }
        .stat-card {
            background-color: white;
            border-left: 5px solid var(--primary-color);
            padding: 15px;
        }
        .key-list-item {
            border-left: 5px solid #0D6EFD; /* Biru Bootstrap */
            margin-bottom: 8px;
            padding: 10px;
            border-radius: 6px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
            background-color: #f9f9fc;
        }
        .key-input-group .form-control {
            border-radius: 8px;
        }
    </style>
    @include('partials.meta_pixel')
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-light bg-white fixed-top">
    <div class="container">
        <a class="btn btn-outline-primary me-3" href="{{ route('home') }}">
            <i class="fas fa-arrow-left me-2"></i> Kembali
        </a>
        <span class="navbar-brand fw-bold">👤 Pengaturan Profil</span>
        
         <div class="d-flex align-items-center gap-3 ms-auto">
             <span class="text-muted small">Halo, {{ Auth::user()->name }}</span>
         </div>
    </div>
</nav>


<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-lg-9 col-xl-8">
            
            <div class="card p-0 mb-4">
                <div class="profile-header">
                    <h3 class="mb-0 fw-bold">Pengaturan Akun</h3>
                    <p class="small opacity-75">Kelola Key API dan Token Anda.</p>
                </div>
                <div class="card-body p-4 pt-3">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="stat-card">
                                <i class="fas fa-coins text-warning me-2 fs-5"></i>
                                <h5 class="fw-bold mb-0 mt-1 d-inline-block">Token Tersisa:</h5>
                                <span class="float-end fs-4 fw-bold text-primary">{{ Auth::user()->credits }}</span>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="stat-card" style="border-left-color: #28A745;">
                                <i class="fas fa-user-circle text-success me-2 fs-5"></i>
                                <h5 class="fw-bold mb-0 mt-1 d-inline-block">Akun:</h5>
                                <span class="float-end fs-6 text-muted mt-2">{{ Auth::user()->email }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card mb-4 border-success">
                <div class="card-header bg-success text-white fw-bold">
                    <i class="fas fa-play-circle me-2"></i> Fitur Kredit Token Gratis
                </div>
                <div class="card-body">
                    <h6 class="fw-bold mb-3">Dapatkan Token Tambahan!</h6>
                    
                    <div class="d-flex justify-content-between align-items-center key-list-item bg-light">
                        <div>
                            <i class="fas fa-ad text-danger me-2"></i> Nonton Iklan Video (30 Detik)
                        </div>
                        <button type="button" class="btn btn-sm btn-outline-success fw-bold" onclick="claimToken('ad')">
                            +1 Token <i class="fas fa-gift ms-1"></i>
                        </button>
                    </div>

                    <div class="d-flex justify-content-between align-items-center key-list-item bg-light mt-2">
                        <div>
                            <i class="fas fa-check-double text-info me-2"></i> Misi: Selesaikan Draf Bab I-V
                        </div>
                         <button type="button" class="btn btn-sm btn-outline-primary fw-bold" onclick="claimToken('mission')">
                            +5 Token
                        </button>
                    </div>
                </div>
            </div>

            <div class="card mb-5">
                <div class="card-header bg-dark text-white fw-bold"><i class="fas fa-key me-2"></i> Manajemen API Keys Gemini</div>
                <div class="card-body">
                    
                    <h6 class="fw-bold mb-3 text-muted">Tambahkan/Update Key Baru</h6>
                    <form id="addApiKeyForm" class="mb-4 key-input-group">
                        @csrf
                        <div class="row g-2">
                            <div class="col-md-5">
                                <input type="text" id="newKeyInput" name="key" class="form-control" placeholder="Masukkan GEMINI_API_KEY..." required>
                            </div>
                            <div class="col-md-4">
                                <input type="text" id="newKeyName" name="name" class="form-control" placeholder="Nama Key (Contoh: Key Utama)">
                            </div>
                            <div class="col-md-3">
                                <button type="submit" id="addApiKeyBtn" class="btn btn-success w-100 fw-bold">Simpan Key</button>
                            </div>
                        </div>
                        <div id="keyErrorAlert" class="alert alert-danger mt-3" style="display:none;"></div>
                    </form>

                    <hr>

                    <h6 class="fw-bold mt-4 mb-3 text-muted">Key Aktif Tersimpan (Total: {{ $apiKeys->count() }})</h6>
                    
                    @if($apiKeys->isEmpty())
                        <p class="text-muted small">Belum ada API Key yang tersimpan.</p>
                    @else
                        <div class="list-group">
                            @foreach($apiKeys as $key)
                                <div class="list-group-item key-list-item d-flex justify-content-between align-items-center p-3">
                                    <div>
                                        <strong class="text-dark">{{ $key->name }}</strong> 
                                        <small class="text-muted ms-3">Key: ...{{ substr($key->key, -6) }}</small>
                                    </div>
                                    <div>
                                        <span class="me-3 small text-muted">Dipakai: {{ $key->usage_count }}</span>
                                        @if($key->is_active)
                                            <span class="badge bg-primary">AKTIF</span>
                                        @else
                                            <span class="badge bg-secondary">NONAKTIF</span>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
            
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
// --- LOGIKA MISI/TOKEN (FIXED AJAX) ---
function claimToken(type) {
    let typeMessage = type === 'ad' ? 'Token Iklan' : 'Token Misi';
    
    Swal.fire({
        title: `Klaim ${typeMessage}`,
        text: "Memproses permintaan penambahan token...",
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });

    axios.post('{{ route('api.claim.token') }}', { 
        type: type 
    })
    .then(res => {
        Swal.fire({
            title: 'Berhasil!',
            text: res.data.message,
            icon: 'success',
            confirmButtonText: 'OK'
        }).then(() => {
            // Reload halaman untuk memperbarui saldo token di UI
            window.location.reload(); 
        });
    })
    .catch(err => {
        let msg = 'Gagal memproses klaim token.';
        if (err.response && err.response.data && err.response.data.message) {
            msg = err.response.data.message;
        }
        Swal.fire('Error Klaim', msg, 'error');
    });
}


// --- LOGIKA MANAJEMEN API KEY (AJAX) ---
document.getElementById('addApiKeyForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const btn = document.getElementById('addApiKeyBtn');
    const keyErrorAlert = document.getElementById('keyErrorAlert');
    keyErrorAlert.style.display = 'none';

    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span> Menyimpan...';

    const key = document.getElementById('newKeyInput').value;
    const name = document.getElementById('newKeyName').value;

    axios.post('{{ route('api.key.add') }}', { 
        key: key, 
        name: name 
    })
    .then(res => {
        Swal.fire('Sukses!', res.data.message, 'success').then(() => {
            window.location.reload(); 
        });
    })
    .catch(err => {
        let errorMessage = 'Gagal menyimpan Key. Cek koneksi Anda.';
        
        if (err.response) {
            if (err.response.status === 422) {
                errorMessage = err.response.data.message || 'API Key tidak valid atau sudah ada.';
            } else if (err.response.status === 500) {
                errorMessage = 'Terjadi kesalahan fatal di server (500). Cek log PHP/Laravel.';
            }
        }
        
        keyErrorAlert.innerText = errorMessage;
        keyErrorAlert.style.display = 'block';
        Swal.fire('Error!', errorMessage, 'error');
    })
    .finally(() => {
        btn.disabled = false;
        btn.innerHTML = 'Simpan Key';
    });
});
</script>
</body>
</html>