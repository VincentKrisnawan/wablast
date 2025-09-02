@extends('layouts.app')

@section('title', 'Home - WABLAST')

@section('content')
<link rel="stylesheet" href="{{ asset('css/home.css') }}">

<div class="container my-4">

    <ul class="nav nav-pills mb-3" id="blast-tabs" role="tablist">
        <li class="nav-item" role="presentation">
        <button class="nav-link active text-info-emphasis" id="tab-text" data-bs-toggle="pill" data-bs-target="#pane-text" type="button" role="tab">Kirim Teks</button>
        </li>
        <li class="nav-item" role="presentation">
        <button class="nav-link text-info-emphasis" id="tab-image" data-bs-toggle="pill" data-bs-target="#pane-image" type="button" role="tab">Kirim Gambar + Caption</button>
        </li>
    </ul>

     <div class="tab-content" id="blast-tabsContent">

    {{-- ==================== PANE KIRIM TEKS ==================== --}}
    <div class="tab-pane fade show active" id="pane-text" role="tabpanel" aria-labelledby="tab-text">
      <div class="row g-4">
        <div class="col-lg-8">
          <div class="d-flex flex-column gap-4">
            {{-- Upload kontak --}}
            <div class="card-item">
              <h5 class="card-title">Upload Kontak Baru</h5>
              <form id="upload-form" action="{{ route('upload.file') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="mb-3">
                  <label for="file_kontak" class="form-label">Pilih file Excel/CSV</label>
                  <input class="form-control @error('file_kontak') is-invalid @enderror" type="file" id="file_kontak" name="file_kontak" required>
                  @error('file_kontak')
                    <div class="invalid-feedback">{{ $message }}</div>
                  @enderror
                </div>
                <button type="submit" class="btn btn-upload">Upload & Buat Sesi</button>
              </form>
            </div>

            {{-- Daftar sesi --}}
            <div class="card-item">
              <h5 class="card-title">Daftar Sesi Pengiriman</h5>
              <div class="table-container">
                <table class="data-table">
                  <thead>
                    <tr>
                      <th>Session</th>
                      @if(Auth::user()->role === 'admin')
                        <th>Uploader</th>
                      @endif
                      <th>Jumlah Kontak</th>
                      <th>Terkirim</th>
                      <th>Status</th>
                      <th style="white-space:nowrap;">Aksi</th>
                    </tr>
                  </thead>
                  <tbody>
                    @forelse ($sessions as $session)
                      <tr id="session-row-text-{{ $session->id }}" data-status="{{ $session->status }}">
                        <td>
                          Sesi {{ $sessions->total() - (($sessions->currentPage() - 1) * $sessions->perPage()) - $loop->index }}
                        </td>
                        @if(Auth::user()->role === 'admin')
                          <td>{{ $session->batch->user->email ?? 'N/A' }}</td>
                        @endif
                        <td>
                          @php
                            $totalSessionsForThisBatch = ceil($session->batch->total_contacts / 100);
                          @endphp
                          @if($session->session_number == $totalSessionsForThisBatch && $session->batch->total_contacts % 100 != 0)
                            {{ $session->batch->total_contacts % 100 }}
                          @else
                            100
                          @endif
                        </td>
                        <td id="sent-count-text-{{ $session->id }}">{{ $session->messages_count }}</td>
                        <td>
                          @php
                            $statusClass = 'status-pending';
                            if ($session->status == 'done') $statusClass = 'status-berhasil';
                            if ($session->status == 'in_progress') $statusClass = 'status-inprogress';
                          @endphp
                          <span class="status {{ $statusClass }}">{{ strtoupper($session->status) }}</span>
                        </td>
                        <td>
                          <button class="btn btn-primary btn-sm btn-kirim-text" data-session-id="{{ $session->id }}" @if($session->status != 'pending') disabled @endif>Kirim Teks</button>
                          <form class="delete-form d-inline-block" action="{{ route('session.destroy', $session) }}" method="POST">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-danger btn-sm">Hapus</button>
                          </form>
                        </td>
                      </tr>
                    @empty
                      <tr>
                        <td colspan="{{ Auth::user()->role === 'admin' ? '7' : '6' }}" class="text-center p-4">
                          Belum ada sesi, silahkan upload.
                        </td>
                      </tr>
                    @endforelse
                  </tbody>
                </table>
              </div>

              @if ($sessions->hasPages())
                <x-pagination :paginator="$sessions" />
              @endif
            </div>
          </div>
        </div>

        <div class="col-lg-4">
          {{-- Template pesan teks --}}
          <div class="card-item">
            <h5 class="card-title">Template Pesan</h5>
            <form action="{{ route('template.store') }}" method="POST">
              @csrf
              <div class="mb-3">
                <label for="template_text" class="form-label">Isi Pesan Anda:</label>
                <textarea id="template_text" name="template_text" class="message-box @error('template_text') is-invalid @enderror" rows="10" placeholder="Tulis template pesan Anda di sini..." required>{{ $template_text }}</textarea>
                @error('template_text')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>
              <button type="submit" class="btn btn-simpan">Simpan Template</button>
            </form>
          </div>
        </div>
      </div>
    </div>

    {{-- ==================== PANE KIRIM GAMBAR (LAYOUT SAMA) ==================== --}}
    <div class="tab-pane fade" id="pane-image" role="tabpanel" aria-labelledby="tab-image">
      <div class="row g-4">
        <div class="col-lg-8">
          <div class="d-flex flex-column gap-4">
            {{-- Upload kontak (sama) --}}
            <div class="card-item">
              <h5 class="card-title">Upload Kontak Baru</h5>
              <form id="upload-form-image-tab" action="{{ route('upload.file') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="mb-3">
                  <label for="file_kontak_2" class="form-label">Pilih file Excel/CSV</label>
                  <input class="form-control" type="file" id="file_kontak_2" name="file_kontak" required>
                </div>
                <button type="submit" class="btn btn-upload">Upload & Buat Sesi</button>
              </form>
            </div>

            {{-- Daftar sesi (sekarang juga 2 tombol/row) --}}
            <div class="card-item">
              <h5 class="card-title">Daftar Sesi Pengiriman</h5>
              <div class="table-container">
                <table class="data-table">
                  <thead>
                    <tr>
                      <th>Session</th>
                      @if(Auth::user()->role === 'admin')
                        <th>Uploader</th>
                      @endif
                      <th>Jumlah Kontak</th>
                      <th>Terkirim</th>
                      <th>Status</th>
                      <th style="white-space:nowrap;">Aksi</th>
                    </tr>
                  </thead>
                  <tbody>
                    @forelse ($sessions as $session)
                      <tr id="session-row-image-{{ $session->id }}" data-status="{{ $session->status }}">
                        <td>
                          Sesi {{ $sessions->total() - (($sessions->currentPage() - 1) * $sessions->perPage()) - $loop->index }}
                        </td>
                        @if(Auth::user()->role === 'admin')
                          <td>{{ $session->batch->user->email ?? 'N/A' }}</td>
                        @endif
                        <td>
                          @php
                            $totalSessionsForThisBatch = ceil($session->batch->total_contacts / 100);
                          @endphp
                          @if($session->session_number == $totalSessionsForThisBatch && $session->batch->total_contacts % 100 != 0)
                            {{ $session->batch->total_contacts % 100 }}
                          @else
                            100
                          @endif
                        </td>
                        <td id="sent-count-image-{{ $session->id }}">{{ $session->messages_count }}</td>
                        <td>
                          @php
                            $statusClass = 'status-pending';
                            if ($session->status == 'done') $statusClass = 'status-berhasil';
                            if ($session->status == 'in_progress') $statusClass = 'status-inprogress';
                          @endphp
                          <span class="status {{ $statusClass }}">{{ strtoupper($session->status) }}</span>
                        </td>
                        <td>
                          <button class="btn btn-primary btn-sm btn-kirim-image" data-session-id="{{ $session->id }}" @if($session->status != 'pending') disabled @endif>Kirim Gambar dan Caption</button>

                          <form class="delete-form d-inline-block" action="{{ route('session.destroy', $session) }}" method="POST">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-danger btn-sm">Hapus</button>
                          </form>
                        </td>
                      </tr>
                    @empty
                      <tr>
                        <td colspan="{{ Auth::user()->role === 'admin' ? '7' : '6' }}" class="text-center p-4">
                          Belum ada sesi, silahkan upload.
                        </td>
                      </tr>
                    @endforelse
                  </tbody>
                </table>
              </div>

              @if ($sessions->hasPages())
                <x-pagination :paginator="$sessions" />
              @endif
            </div>
          </div>
        </div>

        <div class="col-lg-4">
          {{-- Template Pesan (caption) + upload gambar & preview di bawah tombol simpan --}}
          <div class="card-item">
            <h5 class="card-title">Template Pesan (Caption)</h5>
            <form action="{{ route('template.store') }}" method="POST" id="form-template-image">
              @csrf
              <div class="mb-3">
                <label for="template_text_image" class="form-label">Isi Caption Anda:</label>
                <textarea id="template_text_image" name="template_text" class="message-box @error('template_text') is-invalid @enderror" rows="10" placeholder="Tulis caption di sini..." required>{{ $template_text }}</textarea>
                @error('template_text')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>
              <button type="submit" class="btn btn-simpan">Simpan Template</button>
            </form>

            <hr class="my-4">

            <div class="mb-3">
              <label class="form-label">Upload Gambar</label>
              <input type="file" id="image_file" class="form-control" accept="image/*">
              <div class="form-text">Maks 5MB (jpg/png/webp). Gambar ini dipakai saat tekan tombol <b>Kirim Gambar</b> pada baris sesi.</div>
            </div>

            <div id="image_preview_wrap" class="mb-3 d-none">
              <label class="form-label">Preview</label>
              <img id="image_preview" src="#" alt="Preview" class="img-fluid rounded border">
            </div>
          </div>
        </div>
      </div>
    </div>

  </div>
    <!-- <div class="row mt-4">
        <div class="col-12">
            <div class="card-item bg-light">
                    <h5 class="card-title text-danger">Peringatan!</h5>
                    <p class="text-muted">Tindakan ini akan menghapus semua batch, kontak, sesi, template, dan file yang telah diupload. Tindakan ini tidak dapat dibatalkan.</p>
                    <form id="reset-form" action="{{ route('data.cleanup') }}" method="POST">
                    @csrf
                    <button type="submit" class="btn btn-danger w-100">Reset Semua Data & File</button>
                </form>
            </div>
        </div>
    </div> -->
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {

    // ====== Disable button upload saat submit ======
  const uploadForm = document.getElementById('upload-form');
  if (uploadForm) {
    uploadForm.addEventListener('submit', function() {
      const btn = uploadForm.querySelector('.btn-upload');
      if (!btn) return;
      btn.disabled = true;
      btn.innerHTML = `<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Mengupload...`;
    });
  }
  const uploadForm2 = document.getElementById('upload-form-image-tab');
  if (uploadForm2) {
    uploadForm2.addEventListener('submit', function() {
      const btn = uploadForm2.querySelector('.btn-upload');
      if (!btn) return;
      btn.disabled = true;
      btn.innerHTML = `<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Mengupload...`;
    });
  }

  // ====== Delete confirm ======
  const deleteForms = document.querySelectorAll('.delete-form');
  deleteForms.forEach(form => {
    form.addEventListener('submit', function(event) {
      event.preventDefault();
      Swal.fire({
        title: 'Anda Yakin?',
        text: "Sesi dan kontak terkait akan dihapus secara permanen!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Ya, hapus!',
        cancelButtonText: 'Batal'
      }).then((result) => {
        if (result.isConfirmed) {
          const deleteButton = form.querySelector('button[type="submit"]');
          deleteButton.disabled = true;
          deleteButton.innerHTML = `<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>`;
          event.target.submit();
        }
      });
    });
  });

  // ====== Polling status sesi (dipakai untuk kedua aksi) ======
  function startPolling(sessionId, contextPrefix) {
    // contextPrefix: 'text' atau 'image' (untuk targetting elemen)
    const row = document.getElementById(`session-row-${contextPrefix}-${sessionId}`);
    if (!row) return;

    const statusSpan = row.querySelector('.status');
    const statusTd = statusSpan ? statusSpan.parentElement : row;
    const sentCountCell = document.getElementById(`sent-count-${contextPrefix}-${sessionId}`);

    const pollInterval = setInterval(() => {
      const url = `/session/${sessionId}/status?_=${new Date().getTime()}`;
      fetch(url)
        .then(res => {
          if (!res.ok) throw new Error('Pemeriksaan status server gagal. Silakan refresh halaman.');
          return res.json();
        })
        .then(statusData => {
          if (sentCountCell) sentCountCell.textContent = statusData.sent_count;

          if (statusData.status === 'in_progress') {
            statusTd.innerHTML = `<span class="status status-inprogress">IN_PROGRESS</span>`;
            lockRowButtons(row, true);
          } else if (statusData.status === 'done') {
            clearInterval(pollInterval);
            statusTd.innerHTML = `<span class="status status-berhasil">DONE</span>`;
            lockRowButtons(row, true); // selesai -> tetap disabled
          } else if (statusData.status === 'failed') {
            clearInterval(pollInterval);
            statusTd.innerHTML = `<span class="status status-terkendala">FAILED</span>`;
            lockRowButtons(row, false);
          }
        })
        .catch(err => {
          console.error('Polling error:', err);
          Swal.fire({ icon: 'error', title: 'Polling Gagal', text: err.message });
          clearInterval(pollInterval);
        });
    }, 3000);
  }

  function lockRowButtons(rowEl, locked) {
    const t = rowEl.querySelector('.btn-kirim-text');
    const i = rowEl.querySelector('.btn-kirim-image');
    [t,i].forEach(btn => {
      if (!btn) return;
      btn.disabled = locked;
      if (!locked) btn.textContent = btn.classList.contains('btn-kirim-text') ? 'Kirim Teks' : 'Kirim Gambar';
    });
  }

  // ====== Kirim TEKS (kedua tab) ======
  function attachSendTextHandlers() {
    const buttons = document.querySelectorAll('.btn-kirim-text');
    buttons.forEach(btn => {
      btn.addEventListener('click', function () {
        const sessionId = this.dataset.sessionId;
        const row = this.closest('tr');
        const contextPrefix = row?.id?.includes('-image-') ? 'image' : 'text';

        // UI lock
        lockRowButtons(row, true);
        this.innerHTML = `<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>`;
        const statusTd = row.querySelector('.status')?.parentElement || row;
        statusTd.innerHTML = `<span class="status status-inprogress">STARTING</span>`;

        fetch(`/session/${sessionId}/send`, {
          method: 'POST',
          headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Content-Type': 'application/json',
            'Accept': 'application/json',
          }
        })
        .then(response => {
          if (!response.ok) return response.json().then(e => { throw new Error(e.message || 'Gagal memulai proses.'); });
          return response.json();
        })
        .then(data => {
          Swal.fire({ icon: 'info', title: 'Info', text: data.message, timer: 2500, showConfirmButton: false });
          startPolling(sessionId, contextPrefix);
        })
        .catch(error => {
          Swal.fire({ icon: 'error', title: 'Gagal Memulai!', text: error.message });
          lockRowButtons(row, false);
          statusTd.innerHTML = `<span class="status status-pending">PENDING</span>`;
        });
      });
    });
  }

  // ====== Upload gambar + preview (panel kanan tab image) ======
  const imageInput   = document.getElementById('image_file');
  const previewWrap  = document.getElementById('image_preview_wrap');
  const previewImg   = document.getElementById('image_preview');
  let selectedImageFile = null;

  imageInput?.addEventListener('change', () => {
    const file = imageInput.files[0];
    selectedImageFile = file || null;
    if (!file) {
      previewWrap.classList.add('d-none');
      previewImg.src = '#';
      return;
    }
    const reader = new FileReader();
    reader.onload = (e) => {
      previewImg.src = e.target.result;
      previewWrap.classList.remove('d-none');
    };
    reader.readAsDataURL(file);
  });

  // ====== Kirim GAMBAR (kedua tab) ======
  function attachSendImageHandlers() {
    const buttons = document.querySelectorAll('.btn-kirim-image');
    buttons.forEach(btn => {
      btn.addEventListener('click', async function () {
        const sessionId = this.dataset.sessionId;
        const row = this.closest('tr');
        const contextPrefix = row?.id?.includes('-image-') ? 'image' : 'text';

        if (!selectedImageFile) {
          Swal.fire({ icon:'warning', title:'Pilih Gambar', text:'Silakan upload/pilih gambar di panel kanan tab "Kirim Gambar + Caption" terlebih dahulu.' });
          return;
        }
        const captionField = document.getElementById('template_text_image');
        const caption = captionField ? captionField.value : '';
        if (!caption) {
          Swal.fire({ icon:'warning', title:'Caption Kosong', text:'Isi caption pada panel kanan tab "Kirim Gambar + Caption".' });
          return;
        }

        // UI lock
        lockRowButtons(row, true);
        this.innerHTML = `<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>`;
        const statusTd = row.querySelector('.status')?.parentElement || row;
        statusTd.innerHTML = `<span class="status status-inprogress">STARTING</span>`;

        const body = new FormData();
        body.append('_token', '{{ csrf_token() }}');
        body.append('caption', caption);
        body.append('mode', 'base64');         // kirim base64
        body.append('image_file', selectedImageFile);

        try {
          const res = await fetch(`/session/${sessionId}/send-image`, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
            body
          });
          const data = await res.json();
          if (!res.ok) throw new Error(data.message || 'Gagal memulai pengiriman gambar');
          Swal.fire({ icon:'success', title:'Dimulai', text:data.message, timer:2000, showConfirmButton:false });
          startPolling(sessionId, contextPrefix);
        } catch (e) {
          Swal.fire({ icon:'error', title:'Gagal', text:e.message });
          lockRowButtons(row, false);
          statusTd.innerHTML = `<span class="status status-pending">PENDING</span>`;
        }
      });
    });
  }

  // ====== Auto reattach polling untuk sesi in_progress (kedua tab) ======
  function reattachPollingOnLoad() {
    const rows = document.querySelectorAll('tr[data-status="in_progress"]');
    rows.forEach(row => {
      lockRowButtons(row, true);
      const idAttr = row.id || '';
      const sessionId = (row.querySelector('.btn-kirim-text') || row.querySelector('.btn-kirim-image'))?.dataset.sessionId;
      if (!sessionId) return;
      const contextPrefix = idAttr.includes('-image-') ? 'image' : 'text';
      startPolling(sessionId, contextPrefix);
    });
  }

  // ====== Flash dari session ======
  @if (session('success'))
    Swal.fire({ icon: 'success', title: 'Berhasil!', text: '{{ session('success') }}', timer: 3000, showConfirmButton: false });
  @endif
  @if (session('error'))
    Swal.fire({ icon: 'error', title: 'Gagal!', text: '{{ session('error') }}' });
  @endif

  // ====== Autoresize textarea ======
  function attachAutoResize(id) {
    const ta = document.getElementById(id);
    if (!ta) return;
    const autoResize = () => { ta.style.height = 'auto'; ta.style.height = ta.scrollHeight + 'px'; };
    ta.addEventListener('input', autoResize, false);
    autoResize();
  }
  attachAutoResize('template_text');
  attachAutoResize('template_text_image');

  // ====== Init handlers ======
  attachSendTextHandlers();
  attachSendImageHandlers();
  reattachPollingOnLoad();

});
</script>
@endsection
