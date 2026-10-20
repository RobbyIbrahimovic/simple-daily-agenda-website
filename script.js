// jQuery Interactivity & Client-Side Validation

$(document).ready(function () {

    // 1. Bersihkan URL dari parameter ?msg= (Anti Notifikasi Berulang saat Refresh)
    if (window.location.search.includes('msg=')) {
        window.history.replaceState({}, document.title, window.location.pathname);
    }

    // 2. Toggle Form Tambah Agenda (Interactive Slide)
    $("#btnToggleForm").click(function () {
        $("#formSection").slideToggle(350, function () {
            if ($("#formSection").is(":visible")) {
                $("#toggleText").text("Sembunyikan Form");
                $("#iconToggle").removeClass("bi-plus-square-fill").addClass("bi-dash-square-fill");
            } else {
                $("#toggleText").text("Tambah Agenda Baru");
                $("#iconToggle").removeClass("bi-dash-square-fill").addClass("bi-plus-square-fill");
            }
        });
    });

    // Tombol Batal pada Form
    $("#btnBatal").click(function () {
        $("#formSection").slideUp(350);
        $("#toggleText").text("Tambah Agenda Baru");
        $("#iconToggle").removeClass("bi-dash-square-fill").addClass("bi-plus-square-fill");
        $("#clientAlert").addClass("d-none");
    });

    // Auto-hide Notifikasi Server PHP setelah 4 Detik
    setTimeout(function () {
        $("#serverAlert").fadeOut("slow");
    }, 4000);

    // Menutup Alert Klien
    $("#btnCloseClientAlert").click(function () {
        $("#clientAlert").addClass("d-none");
    });

    // 3. Validasi Form Sisi Klien (jQuery Client-Side Validation)
    $("#agendaForm").submit(function (e) {
        var judul = $("#judul_kegiatan").val().trim();
        var kategori = $("#kategori").val();
        var tanggal = $("#tanggal_kegiatan").val();

        var errorMessage = "";

        if (judul === "") {
            errorMessage = "Judul Kegiatan tidak boleh kosong!";
            $("#judul_kegiatan").focus();
        } else if (kategori === "") {
            errorMessage = "Silakan pilih Kategori Kegiatan!";
            $("#kategori").focus();
        } else if (tanggal === "") {
            errorMessage = "Tanggal & Waktu Kegiatan wajib ditentukan!";
            $("#tanggal_kegiatan").focus();
        }

        if (errorMessage !== "") {
            e.preventDefault();
            $("#clientAlertMsg").text(errorMessage);
            $("#clientAlert").removeClass("d-none").hide().fadeIn(250);
            $("html, body").animate({ scrollTop: $("#formSection").offset().top - 30 }, "fast");
        }
    });

});