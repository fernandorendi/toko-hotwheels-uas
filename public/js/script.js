document.addEventListener("DOMContentLoaded", function () {
    
    // 1. Konfirmasi sebelum melakukan Pembelian Langsung
    const buyForms = document.querySelectorAll("form[action*='transactions.store']");
    
    buyForms.forEach(form => {
        form.addEventListener("submit", function (event) {
            // Ambil nama produk dari baris tabel yang sama
            const row = form.closest("tr");
            const productName = row.querySelector("td b").innerText;
            const productPrice = row.querySelectorAll("td")[3].innerText;

            const konfirmasi = confirm(`Apakah Anda yakin ingin membeli langsung "${productName}" seharga ${productPrice}?`);
            
            if (!konfirmasi) {
                event.preventDefault(); // Batalkan pengiriman form jika memilih 'Cancel'
            }
        });
    });

    // 2. Alert Otomatis jika Stok Menipis saat Admin melihat halaman
    const stockCells = document.querySelectorAll("table border tr td");
    stockCells.forEach(cell => {
        if (cell.innerText.includes("unit") || cell.innerText.includes("pcs")) {
            const stockValue = parseInt(cell.innerText);
            if (stockValue < 3 && stockValue > 0) {
                cell.innerHTML += " <span style='color:orange;'><b>(Hampir Habis!)</b></span>";
            } else if (stockValue === 0) {
                cell.innerHTML = "<span style='color:red;'><b>Kosong</b></span>";
            }
        }
    });
});