(function () {
	'use strict';

	var config = window.cafeDashboard;
	if (!config) {
		return;
	}

	var canvas = document.getElementById('cafe-revenue-chart');
	if (canvas && window.Chart) {
		new window.Chart(canvas, {
			type: 'bar',
			data: {
				labels: config.chart.labels,
				datasets: [{ label: config.i18n.revenue, data: config.chart.values, backgroundColor: '#8B5A3C' }]
			},
			options: {
				responsive: true,
				maintainAspectRatio: false,
				plugins: { legend: { display: false } },
				scales: { y: { beginAtZero: true } }
			}
		});
	}

	document.addEventListener('click', function (event) {
		var button = event.target.closest('.cafe-mark-delivered');
		if (!button) {
			return;
		}
		button.disabled = true;

		var body = new URLSearchParams({
			action: 'cafe_mark_delivered',
			nonce: config.nonce,
			order_id: button.dataset.orderId
		});

		fetch(config.ajaxUrl, { method: 'POST', credentials: 'same-origin', body: body })
			.then(function (response) { return response.json(); })
			.then(function (json) {
				if (!json || !json.success) {
					throw new Error(json && json.data && json.data.message ? json.data.message : config.i18n.error);
				}
				button.closest('tr').remove();

				var countEl = document.querySelector('.cafe-pending-count');
				if (countEl) {
					var current = parseInt(countEl.textContent.replace(/\D/g, ''), 10);
					if (!isNaN(current) && current > 0) {
						countEl.textContent = String(current - 1);
					}
				}
			})
			.catch(function (error) {
				button.disabled = false;
				window.alert(error && error.message ? error.message : config.i18n.error);
			});
	});
})();
