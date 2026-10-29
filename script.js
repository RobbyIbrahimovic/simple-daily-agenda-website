// Script.js For Simple Daily Agenda Web App
function konfirmasiSelesai(judul, tanggalStr) {
    var tglAgenda = new Date(tanggalStr);
    var sekarang  = new Date();

    if (tglAgenda > sekarang) {
        return confirm(
            "PERINGATAN!\n\n" +
            "Agenda '" + judul + "' dijadwalkan untuk masa yang mendatang (" + tanggalStr + ").\n\n" +
            "Apakah Anda YAKIN ingin menandai agenda ini SELESAI lebih awal dari jadwal?"
        );
    }
    return confirm("Tandai agenda '" + judul + "' sebagai Selesai?");
}

$(document).ready(function () {

    if (window.location.search.includes('msg=')) {
        window.history.replaceState({}, document.title, window.location.pathname);
    }

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

    // Tombol Batal
    $("#btnBatal").click(function () {
        $("#formSection").slideUp(350);
        $("#toggleText").text("Tambah Agenda Baru");
        $("#iconToggle").removeClass("bi-dash-square-fill").addClass("bi-plus-square-fill");
        $("#clientAlert").addClass("d-none");
    });

    setTimeout(function () {
        $("#serverAlert").fadeOut("slow");
    }, 4000);

    $("#btnCloseClientAlert").click(function () {
        $("#clientAlert").addClass("d-none");
    });

    $("#agendaForm").submit(function (e) {
        var judul   = $("#judul_kegiatan").val().trim();
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
        } else {
            var inputDate = new Date(tanggal);
            var nowDate   = new Date();

            if (inputDate < nowDate) {
                errorMessage = "Waktu kegiatan tidak boleh di masa lalu! Silakan pilih waktu mendatang.";
                $("#tanggal_kegiatan").focus();
            }
        }

        if (errorMessage !== "") {
            e.preventDefault();
            $("#clientAlertMsg").text(errorMessage);
            $("#clientAlert").removeClass("d-none").hide().fadeIn(250);
            $("html, body").animate({ scrollTop: $("#formSection").offset().top - 30 }, "fast");
        }
    });

});