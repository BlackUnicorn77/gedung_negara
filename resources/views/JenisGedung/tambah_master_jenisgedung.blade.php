<!doctype html>
@include('template/header')
<body>
  <!-- Begin Page Content -->
<div class="container-fluid">

<div class="row">
  <div class="col">
    <div class="card shadow mb-4">
      <div class="card-header bg-primary py-3">
        <h6 class="m-0 font-weight-bold text-white">TAMBAH DATA JENIS GEDUNG</h6>
      </div>
      <div class="card-body">

        <div class="table-responsive">
          <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
            <form action="{{ url('tambah_master_jenisgedung_post') }}" method="post">
            @csrf
              <div class="form-group">
                <label hidden>id:</label>
                <input type="text" name="#" class="form-control" hidden>
              </div>
              <div class="form-group">
                <label >Nama Jenis Gedung:</label>
                <input type="text" class="form-control" placeholder="Masukkan Nama jenis Gedung"  name="nama_jenis_gedung">
              </div>
              <button type="submit"  class="btn btn-primary float-left mt-2">Submit</button>
              <a class="btn btn-warning float-left mt-2" href="{{ url('/master_jenisgedung') }}" role="button">Kembali</a>
            </form>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>
</div>


<!-- @include('template/footer') -->
</body>
