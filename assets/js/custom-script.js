document.addEventListener('DOMContentLoaded', function() {
    // انتخاب عنصر canvas که نمودار در آن رسم می‌شود
    var ctx = document.getElementById('myChart').getContext('2d');

    // ایجاد نمودار با استفاده از Chart.js
    var myChart = new Chart(ctx, {
        type: 'bar', // نوع نمودار: bar, line, pie و غیره
        data: {
            labels: ['January', 'February', 'March', 'April', 'May', 'June', 'July'],
            datasets: [{
                label: 'فروش ماهانه',
                data: [12, 19, 3, 5, 2, 3, 10], // داده‌های مربوط به هر ماه
                backgroundColor: 'rgba(75, 192, 192, 0.2)', // رنگ پس‌زمینه میله‌ها
                borderColor: 'rgba(75, 192, 192, 1)', // رنگ لبه‌ها
                borderWidth: 1 // ضخامت لبه‌ها
            }]
        },
        options: {
            scales: {
                y: {
                    beginAtZero: true // محور y از صفر شروع شود
                }
            }
        }
    });
});