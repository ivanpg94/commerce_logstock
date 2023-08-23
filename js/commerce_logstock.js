
console.log('dsds');


document.addEventListener("DOMContentLoaded", function() {
  // Obtiene todas las filas de la tabla
  const rows = document.querySelectorAll("tbody tr");

  // Recorre las filas y verifica el valor de mi-clase-stockinicial
  rows.forEach(row => {
    const stockInicialElement = row.querySelector(".logstock-table-stockfinal");
    const stockInicialValue = parseInt(stockInicialElement.textContent);

    // Si el stock inicial es 0 o menor, aplica el color de fondo rojo a toda la fila
    if (stockInicialValue <= 0) {
      row.style.backgroundColor = "#ff00007a";
    }
  });
});





