<!doctype html>
@include('template/header')
<body>
  <!-- Begin Page Content -->
<div class="container-fluid">

<div class="row">
  <div class="col">
    <div class="card shadow mb-4">
      <div class="card-header bg-primary py-3">
        <h6 class="m-0 font-weight-bold text-white">TAMBAH DATA GEDUNG DENGAN EXCEL</h6>
      </div>
      <div class="card-body">

        <div class="table-responsive">
          <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
            <thead>
              <div class="form-group">
                <label >Input dari Excel:</label>
                <input type="file" name="#" class="form-control">
              </div>
              <div class="form-group">
                <label >Lokasi Gedung:</label>
                  <select id="select" class="form-control" name="">
                  <option value="">Lokasi Gedung</option>
                  <option value="1">Option #1</option>
                  <option value="2">Option #2</option>
                  <option value="3">Option #3</option>
                  </select>
              </div>
              <button type="submit"  class="btn btn-primary float-left mt-2">Submit</button>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="card shadow mb-4">
  <div class="card-header bg-primary py-3">
    <h6 class="m-0 font-weight-bold text-white">TAMBAH DATA GEDUNG</h6>
  </div>
  <div class="card-body">

    <div class="table-responsive">
      <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
      <form  enctype="multipart/form-data" method='post'>

    <div class="form-group">
      <label>Nama Gedung:</label>
      <input type="text" class="form-control" placeholder="Nama Gedung"  name="nama" required>
    </div>

    <div class="form-group">
      <label >Lokasi Gedung:</label>
            <select id="select" class="form-control" name="lokasi">
              <option value="">Lokasi Gedung</option>
              <option value="1">Option #1</option>
              <option value="2">Option #2</option>
              <option value="3">Option #3</option>
            </select>
    </div>

    <div class="form-group">
      <label >Jenis Gedung:</label>
            <select id="select" class="form-control" name="kategori">
              <option value="">Jenis Gedung</option>
              <option value="1">Option #1</option>
              <option value="2">Option #2</option>
              <option value="3">Option #3</option>
            </select>
    </div>
    
    <div class="form-group">
      <label>BT:</label>
      <input type="number" class="form-control" placeholder="0"  name="bujur">
    </div>

    <div class="form-group">
      <label >LS:</label>
      <input type="number" class="form-control" placeholder="0" name="lintang">
    </div>  
    
    <div class="form-group">
      <label>Legalitas:</label>
      <input type="text" class="form-control" placeholder="Legalitas"  name="legalitas">
    </div>

    <div class="form-group">
      <label>Tipe Milik:</label>
      <input type="text" class="form-control" placeholder="Tipe Milik"  name="tipe_milik">
    </div>

    <div class="form-group">
      <label>Alas Hak:</label>
      <input type="text" class="form-control" placeholder="Alas Hak"  name="alas_hak">
    </div>

    <div class="form-group">
      <label>Luas Lahan:</label>
      <input type="number" class="form-control" placeholder="0"  name="luas_lahan">
    </div>

    <div class="form-group">
      <label>Jumlah Lantai:</label>
      <input type="number" class="form-control" placeholder="0"  name="jumlah_lantai">
    </div>

    <div class="form-group">
      <label>Luas Bangunan:</label>
      <input type="number" class="form-control" placeholder="0"  name="luas_bangunan">
    </div>

    <div class="form-group">
      <label>Tinggi Bangunan:</label>
      <input type="number" class="form-control" placeholder="0"  name="tinggi_bangunan">
    </div>

    <div class="form-group">
      <label>Klas Tinggi:</label>
      <input type="text" class="form-control" placeholder="Klas Tinggi"  name="klas_tinggi">
    </div>

    <div class="form-group">
      <label>Kompleks:</label>
      <input type="text" class="form-control" placeholder="Kompleks"  name="kompleks">
    </div>

    <div class="form-group">
      <label>Kepadatan:</label>
      <input type="text" class="form-control" placeholder="kepadatan"  name="kepadatan">
    </div>

    <div class="form-group">
      <label>Pemanensi:</label>
      <input type="text" class="form-control" placeholder="Pemanensi"  name="permanensi">
    </div>

    <div class="form-group">
      <label>Risk Bakar:</label>
      <input type="text" class="form-control" placeholder="Risk Bakar"  name="risk_bakar">
    </div>

    <div class="form-group">
      <label>Penangkal:</label>
      <input type="text" class="form-control" placeholder="Penangkal"  name="penangkal">
    </div>

    <button type="submit"  class="btn btn-primary float-left mt-2">Submit</button>
    <a class="btn btn-warning float-left mt-2" href="{{url('master_gedung')}}" role="button">Kembali</a>
   
        </form>
        <tbody>
          
        </tbody>
      </table>
    </div>
  </div>
</div>


<!-- @include('template/footer') -->
</body>