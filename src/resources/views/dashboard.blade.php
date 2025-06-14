<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Query Optimizer Dashboard</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/luxon@3.0.4/build/global/luxon.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/chartjs-adapter-luxon@1.3.1"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
  <script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>

  <script>
    tailwind.config = {
      theme: {
        extend: {
          colors: {
            primary: {
              50: '#f0f9ff',
              100: '#e0f2fe',
              500: '#0ea5e9',
              600: '#0284c7',
            }
          }
        }
      }
    }
  </script>
  <style>
    .query-row:hover {
      background-color: #f8fafc;
    }

    .sql-code {
      font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace;
      font-size: 0.875rem;
    }

    .badge {
      display: inline-block;
      padding: 0.25rem 0.5rem;
      border-radius: 0.25rem;
      font-size: 0.75rem;
      font-weight: 500;
    }

    .chart-container {
      position: relative;
      height: 300px;
    }

    #modalExplainContent pre {
      background-color: #2d2d2d;
      color: #f8f8f2;
      padding: 1em;
      border-radius: 0.25rem;
      overflow-x: auto;
      margin-top: 1em;
      margin-bottom: 1em;
    }
    #modalExplainContent code {
      font-family: monospace;
      background-color: rgba(128, 128, 128, 0.1);
      padding: 0.2em 0.4em;
      border-radius: 0.2em;
    }
    #modalExplainContent strong {
      font-weight: bold;
    }
    #modalExplainContent p {
      margin-bottom: 0.75rem;
    }
    #modalExplainContent ul {
      list-style-type: disc;
      margin-left: 1.5rem;
      margin-bottom: 0.75rem;
    }
    #modalExplainContent li {
      margin-bottom: 0.25rem;
    }
  </style>
</head>

<body class="bg-gray-50 text-gray-800 p-4">
  <div class="container mx-auto">
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
      <div>
        <h1 class="text-2xl font-bold text-gray-800">Query Optimizer Dashboard</h1>
        <p class="text-gray-600">Monitor and analyze database query performance</p>
      </div>
      <div class="flex gap-2">
        <button id="refreshBtn"
          class="px-4 py-2 bg-primary-500 hover:bg-primary-600 text-white rounded-md flex items-center gap-2">
          <i class="fas fa-sync-alt"></i> Refresh
        </button>
      </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
      <div class="bg-white rounded-lg shadow p-4">
        <div class="text-gray-500">Total Queries</div>
        <div id="totalQueries" class="text-2xl font-bold">0</div>
      </div>
      <div class="bg-white rounded-lg shadow p-4">
        <div class="text-gray-500">Total Time</div>
        <div id="totalTime" class="text-2xl font-bold">0 ms</div>
      </div>
      <div class="bg-white rounded-lg shadow p-4">
        <div class="text-gray-500">Avg. Time</div>
        <div id="avgTime" class="text-2xl font-bold">0 ms</div>
      </div>
      <div class="bg-white rounded-lg shadow p-4">
        <div class="text-gray-500">Slowest Query</div>
        <div id="slowestQuery" class="text-2xl font-bold">0 ms</div>
      </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
      <div class="bg-white rounded-lg shadow p-4">
        <h2 class="text-lg font-semibold mb-4">Query Execution Times</h2>
        <div class="chart-container">
          <canvas id="timeChart"></canvas>
        </div>
      </div>
      <div class="bg-white rounded-lg shadow p-4">
        <h2 class="text-lg font-semibold mb-4">Query Distribution Over Time</h2>
        <div class="chart-container">
          <canvas id="timeSeriesChart"></canvas>
        </div>
      </div>
    </div>

    <div class="bg-white rounded-lg shadow overflow-hidden mb-6">
      <div class="px-4 py-3 border-b flex flex-col md:flex-row md:items-center justify-between gap-2">
        <h2 class="text-lg font-semibold">Query Details</h2>
        <div class="flex gap-2">
          <div class="relative">
            <input type="text" id="searchInput" placeholder="Search queries..."
              class="pl-10 pr-4 py-2 border rounded-md w-full md:w-64">
            <i class="fas fa-search absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
          </div>
          <select id="timeFilter" class="border rounded-md px-3 py-2">
            <option value="all">All Times</option>
            <option value="fast">Fast (&lt; 5ms)</option>
            <option value="medium">Medium (5-20ms)</option>
            <option value="slow">Slow (&gt; 20ms)</option>
          </select>
        </div>
      </div>
      <div class="overflow-x-auto">
        <table class="w-full">
          <thead class="bg-gray-50">
            <tr>
              <th class="py-3 px-4 text-left">Time (ms)</th>
              <th class="py-3 px-4 text-left">Timestamp</th>
              <th class="py-3 px-4 text-left">SQL Query</th>
              <th class="py-3 px-4 text-left">Bindings</th>
              <th class="py-3 px-4 text-left">Type</th>
            </tr>
          </thead>
          <tbody id="queryTableBody" class="divide-y">
          </tbody>
        </table>
      </div>
      <div class="px-4 py-3 border-t flex justify-between items-center">
        <div id="summaryInfo" class="text-sm text-gray-600">Showing 0 queries</div>
        <div class="flex gap-2">
          <button id="prevBtn" class="px-3 py-1 border rounded-md disabled:opacity-50">&laquo; Prev</button>
          <span id="pageInfo" class="px-3 py-1">Page 1</span>
          <button id="nextBtn" class="px-3 py-1 border rounded-md disabled:opacity-50">Next &raquo;</button>
        </div>
      </div>
    </div>
  </div>

  <div id="explainModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden z-50">
    <div class="bg-white rounded-lg shadow p-6 max-w-3xl w-full relative">
      <button onclick="closeExplainModal()"
        class="absolute top-2 right-3 text-gray-500 hover:text-black text-2xl">&times;</button>
      <h3 class="text-lg font-bold mb-4">EXPLAIN Output</h3>
      <div id="modalExplainContent" class="bg-gray-100 p-4 text-sm rounded overflow-y-auto" style="max-height: 300px;">
        Loading explanation...
      </div>
    </div>
  </div>

  <script>
    const PAGE_SIZE = 10;
    let currentPage = 1;
    let allData = [];
    let filteredData = [];

    const htmlBlockPlaceholders = {};
    let placeholderCounter = 0;

    const elements = {
      timeChart: document.getElementById('timeChart'),
      timeSeriesChart: document.getElementById('timeSeriesChart'),
      queryTableBody: document.getElementById('queryTableBody'),
      searchInput: document.getElementById('searchInput'),
      timeFilter: document.getElementById('timeFilter'),
      prevBtn: document.getElementById('prevBtn'),
      nextBtn: document.getElementById('nextBtn'),
      pageInfo: document.getElementById('pageInfo'),
      summaryInfo: document.getElementById('summaryInfo'),
      totalQueries: document.getElementById('totalQueries'),
      totalTime: document.getElementById('totalTime'),
      avgTime: document.getElementById('avgTime'),
      slowestQuery: document.getElementById('slowestQuery'),
      refreshBtn: document.getElementById('refreshBtn'),
      explainModal: document.getElementById('explainModal'), 
      modalExplainContent: document.getElementById('modalExplainContent')
    };

    const timeChart = new Chart(elements.timeChart, {
      type: 'bar',
      data: {
        labels: [],
        datasets: [{
          label: 'Execution Time (ms)',
          data: [],
          backgroundColor: '#0ea5e9',
          borderColor: '#0284c7',
          borderWidth: 1
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
          y: {
            beginAtZero: true,
            title: {
              display: true,
              text: 'Time (ms)'
            }
          },
          x: {
            title: {
              display: true,
              text: 'Query Index'
            }
          }
        },
        plugins: {
          tooltip: {
            callbacks: {
              title: (items) => `Query ${items[0].dataIndex + 1}`,
              afterBody: (items) => {
                const index = items[0].dataIndex;
                return allData[index]?.sql || '';
              }
            }
          }
        }
      }
    });

    const timeSeriesChart = new Chart(elements.timeSeriesChart, {
      type: 'line',
      data: {
        datasets: [{
          label: 'Query Execution Time',
          data: [],
          backgroundColor: 'rgba(14, 165, 233, 0.1)',
          borderColor: '#0ea5e9',
          borderWidth: 2,
          pointRadius: 3,
          tension: 0.1
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
          y: {
            beginAtZero: true,
            title: {
              display: true,
              text: 'Time (ms)'
            }
          },
          x: {
            type: 'time',
            time: {
              unit: 'minute',
              tooltipFormat: 'HH:mm:ss'
            },
            title: {
              display: true,
              text: 'Time'
            }
          }
        },
        plugins: {
          tooltip: {
            callbacks: {
              afterBody: (items) => {
                const index = items[0].dataIndex;
                return allData[index]?.sql || '';
              }
            }
          }
        }
      }
    });

    function formatTime(ms) {
      return ms < 5 ?
        `<span class="text-green-600 font-medium">${ms.toFixed(2)}</span>` :
        ms < 20 ?
        `<span class="text-yellow-600 font-medium">${ms.toFixed(2)}</span>` :
        `<span class="text-red-600 font-medium">${ms.toFixed(2)}</span>`;
    }

    function formatTimestamp(isoString) {
      const dt = luxon.DateTime.fromISO(isoString);
      return dt.toFormat('yyyy-MM-dd HH:mm:ss');
    }

    function getQueryType(sql) {
      if (!sql) return '';
      const firstWord = sql.trim().split(/\s+/)[0].toLowerCase();
      const types = {
        select: 'bg-blue-100 text-blue-800',
        insert: 'bg-green-100 text-green-800',
        update: 'bg-yellow-100 text-yellow-800',
        delete: 'bg-red-100 text-red-800'
      };
      return types[firstWord] || 'bg-gray-100 text-gray-800';
    }

    function renderTableRows(data) {
      elements.queryTableBody.innerHTML = '';

      const startIdx = (currentPage - 1) * PAGE_SIZE;
      const pageData = data.slice(startIdx, startIdx + PAGE_SIZE);

      if (pageData.length === 0) {
        elements.queryTableBody.innerHTML = `
          <tr>
            <td colspan="5" class="py-6 text-center text-gray-500">
              No queries found
            </td>
          </tr>
        `;
        return;
      }

      pageData.forEach((entry, index) => {
        const fullIndex = startIdx + index;
        const row = document.createElement('tr');
        row.className = 'query-row hover:bg-gray-50';
        row.innerHTML = `
          <td class="py-3 px-4">${formatTime(entry.time)}</td>
          <td class="py-3 px-4">${formatTimestamp(entry.timestamp)}</td>
          <td class="py-3 px-4">
            <div class="sql-code max-w-lg truncate" title="${entry.sql}">
              ${entry.sql}
            </div>
          </td>
          <td class="py-3 px-4">
            <div class="max-w-xs truncate" title="${JSON.stringify(entry.bindings)}">
              ${entry.bindings ? JSON.stringify(entry.bindings) : '[]'}
            </div>
          </td>
          <td class="py-3 px-4">
            <span class="badge ${getQueryType(entry.sql)}">
              ${entry.sql.trim().split(/\s+/)[0]}
            </span>
            <button
              class="ml-2 text-blue-600 hover:underline text-sm"
              onclick="getExplain(${fullIndex})"
              title="Explain this query"
            >
              Explain
            </button>
          </td>
        `;
        elements.queryTableBody.appendChild(row);
      });
    }

    function updatePagination(data) {
      const totalPages = Math.ceil(data.length / PAGE_SIZE);

      elements.prevBtn.disabled = currentPage <= 1;
      elements.nextBtn.disabled = currentPage >= totalPages;
      elements.pageInfo.textContent = `Page ${currentPage} of ${totalPages}`;
      elements.summaryInfo.textContent = `Showing ${Math.min(PAGE_SIZE, data.length)} of ${data.length} queries`;
    }

    function updateCharts(data) {
      timeChart.data.labels = data.map((_, i) => i + 1);
      timeChart.data.datasets[0].data = data.map(e => e.time);
      timeChart.update();

      timeSeriesChart.data.datasets[0].data = data.map(e => ({
        x: luxon.DateTime.fromISO(e.timestamp).toJSDate(),
        y: e.time
      }));
      timeSeriesChart.update();
    }

    function updateSummary(data) {
      const totalQueries = data.length;
      const totalTime = data.reduce((sum, e) => sum + e.time, 0);
      const avgTime = totalQueries ? (totalTime / totalQueries).toFixed(2) : 0;
      const slowestQuery = Math.max(...data.map(e => e.time), 0).toFixed(2);

      elements.totalQueries.textContent = totalQueries;
      elements.totalTime.textContent = `${totalTime.toFixed(2)} ms`;
      elements.avgTime.textContent = `${avgTime} ms`;
      elements.slowestQuery.textContent = `${slowestQuery} ms`;
    }

    function filterData() {
      const searchTerm = elements.searchInput.value.toLowerCase();
      const timeFilter = elements.timeFilter.value;

      filteredData = allData.filter(entry => {
        const matchesSearch = entry.sql.toLowerCase().includes(searchTerm) ||
            JSON.stringify(entry.bindings).toLowerCase().includes(searchTerm);

        let matchesTime = true;
        if (timeFilter === 'fast') matchesTime = entry.time < 5;
        else if (timeFilter === 'medium') matchesTime = entry.time >= 5 && entry.time < 20;
        else if (timeFilter === 'slow') matchesTime = entry.time >= 20;

        return matchesSearch && matchesTime;
      });

      currentPage = 1;
      updateCharts(filteredData);
      renderTableRows(filteredData);
      updatePagination(filteredData);
      updateSummary(filteredData);
    }

    async function fetchData() {
      try {
        const response = await fetch("{{ url('api/query-optimizer/metrics') }}");
        const data = await response.json();

        allData = data.sort((a, b) =>
          new Date(b.timestamp) - new Date(a.timestamp)
        );

        filterData();
      } catch (error) {
        console.error('Error fetching query data:', error);
        displayMessage('Error loading query data. Please try again.', 'error');
      }
    }

    function cleanAndFormatExplanation(rawApiResultString) {
      let explanationContent = '';

      try {
        for (const key in htmlBlockPlaceholders) {
          delete htmlBlockPlaceholders[key];
                    JSON.stringify(entry.bindings).toLowerCase().includes(searchTerm);

                let matchesTime = true;
                if (timeFilter === 'fast') matchesTime = entry.time < 5;
                else if (timeFilter === 'medium') matchesTime = entry.time >= 5 && entry.time < 20;
                else if (timeFilter === 'slow') matchesTime = entry.time >= 20;

                return matchesSearch && matchesTime;
            });

            currentPage = 1;
            updateCharts(filteredData);
            renderTableRows(filteredData);
            updatePagination(filteredData);
            updateSummary(filteredData);
        }
      }
       
        async function fetchData() {
            try {
                const response = await fetch("{{ url('api/query-optimizer/metrics') }}");
                const data = await response.json();

                allData = data.sort((a, b) =>
                    new Date(b.timestamp) - new Date(a.timestamp)
                );

                filterData();
            } catch (error) {
                console.error('Error fetching query data:', error);
                displayMessage('Error loading query data. Please try again.', 'error');
            }
        }

  
        function cleanAndFormatExplanation(rawApiResultString) {
            let explanationContent = '';

            try {
                for (const key in htmlBlockPlaceholders) {
                    delete htmlBlockPlaceholders[key];
                }
                placeholderCounter = 0;

                const parsedResult = JSON.parse(rawApiResultString);
                explanationContent = parsedResult.explanation || 'No explanation provided.';

                const htmlBlockRegex = /```html\n([\s\S]*?)\n```/g;

                explanationContent = explanationContent.replace(htmlBlockRegex, (match, htmlContent) => {
                    const placeholder = `<!-- HTML_BLOCK_PLACEHOLDER_${placeholderCounter++} -->`;
                    htmlBlockPlaceholders[placeholder] = htmlContent.replace(/\\n/g, '\n');
                    return placeholder;
                });

                let htmlOutput = marked.parse(explanationContent);

                for (const placeholder in htmlBlockPlaceholders) {
                    htmlOutput = htmlOutput.replace(placeholder, htmlBlockPlaceholders[placeholder]);
                }

                return htmlOutput;

            } catch (error) {
                console.error("Error parsing or formatting explanation:", error);
                return `<p class="text-red-500">Error displaying explanation: ${error.message}. Please check the API response format.</p>`;
            }
        }

       
        async function getExplain(index) {
            const sql = allData[index].sql;
            elements.modalExplainContent.innerHTML = 'Loading explanation...'; // Show loading state
            elements.explainModal.classList.remove("hidden"); // Show modal immediately

            try {
                const response = await fetch("{{ url('api/query-optimizer/explain') }}", {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                        "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]') ?.content
                    },
                    body: JSON.stringify({
                        sql
                    })
                });

                const result = await response.json(); 

                if (response.ok) {
                    const formattedHtml = cleanAndFormatExplanation(JSON.stringify(result));
                    elements.modalExplainContent.innerHTML = formattedHtml;
                } else {
                    console.error("Explain API Error:", result.error || "Unknown error");
                    elements.modalExplainContent.innerHTML = `<p class="text-red-500">Error: ${result.error || 'Failed to get EXPLAIN.'}</p>`;
                }
            } catch (error) {
                console.error("Explain Fetch Error:", error);
                elements.modalExplainContent.innerHTML = '<p class="text-red-500">Failed to connect to EXPLAIN service.</p>';
            }
        }


        function closeExplainModal() {
            elements.explainModal.classList.add("hidden");
        }

        function displayMessage(message, type = 'info') {
            const messageBox = document.createElement('div');
            messageBox.className = `fixed bottom-4 right-4 p-4 rounded-md shadow-lg text-white z-50 ${type === 'error' ? 'bg-red-500' : 'bg-blue-500'}`;
            messageBox.textContent = message;
            document.body.appendChild(messageBox);
            setTimeout(() => {
                messageBox.remove();
            }, 3000); 
        }


        elements.searchInput.addEventListener('input', filterData);
        elements.timeFilter.addEventListener('change', filterData);
        elements.prevBtn.addEventListener('click', () => {
            if (currentPage > 1) {
                currentPage--;
                renderTableRows(filteredData);
                updatePagination(filteredData);
            }
        });
        elements.nextBtn.addEventListener('click', () => {
            const totalPages = Math.ceil(filteredData.length / PAGE_SIZE);
            if (currentPage < totalPages) {
                currentPage++;
                renderTableRows(filteredData);
                updatePagination(filteredData);
            }
        });
        elements.refreshBtn.addEventListener('click', fetchData);

        document.addEventListener('DOMContentLoaded', fetchData);
    </script>
</body>

</html>
