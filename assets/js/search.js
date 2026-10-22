(function () {
  'use strict';

  document.addEventListener('DOMContentLoaded', () => {
    const searchInput = document.getElementById('ajaxSearchInput');
    const subjectFilter = document.getElementById('subjectFilter');
    const resultsContainer = document.getElementById('searchResultsContainer');
    const loadingSpinner = document.getElementById('searchLoadingSpinner');
    const searchCountBadge = document.getElementById('searchCountBadge');

    if (!searchInput || !resultsContainer) {
      return;
    }

    let debounceTimer = null;

    function performSearch() {
      const query = searchInput.value.trim();
      const subject = subjectFilter ? subjectFilter.value : '';
      const apiEndpoint = searchInput.getAttribute('data-endpoint') || '../api/search.php';

      if (loadingSpinner) {
        loadingSpinner.classList.remove('d-none');
      }

      const params = new URLSearchParams({
        q: query,
        subject: subject,
      });

      fetch(`${apiEndpoint}?${params.toString()}`, {
        headers: {
          'Accept': 'application/json',
          'X-Requested-With': 'XMLHttpRequest'
        }
      })
      .then(response => {
        if (!response.ok) {
          throw new Error(`HTTP error! Status: ${response.status}`);
        }
        return response.json();
      })
      .then(data => {
        if (loadingSpinner) {
          loadingSpinner.classList.add('d-none');
        }

        if (data.status === 'success') {
          renderResults(data.data, query);
          if (searchCountBadge) {
            searchCountBadge.textContent = `${data.count} found`;
          }
        } else {
          renderError(data.message || 'Error executing search.');
        }
      })
      .catch(err => {
        if (loadingSpinner) {
          loadingSpinner.classList.add('d-none');
        }
        console.error('AJAX Search error:', err);
        renderError('Unable to connect to search service.');
      });
    }

    function renderResults(items, currentQuery) {
      if (!items || items.length === 0) {
        resultsContainer.innerHTML = `
          <div class="text-center py-5 text-muted">
            <i class="bi bi-search display-6 d-block mb-2"></i>
            <p class="mb-0 fw-medium">No matching items found.</p>
            <small>Try adjusting your search terms or filter criteria.</small>
          </div>
        `;
        return;
      }

      const baseUrl = searchInput.getAttribute('data-base-url') || '';

      const html = items.map(item => {
        const downloadUrl = `${baseUrl}/download.php?id=${item.id}`;
        return `
          <div class="card card-custom mb-3 shadow-none">
            <div class="card-body p-3">
              <div class="d-flex justify-content-between align-items-start gap-2">
                <div>
                  <span class="badge bg-primary-subtle text-primary border border-primary-subtle mb-1">${escapeHtml(item.subject)}</span>
                  <h6 class="card-title mb-1 fw-bold text-truncate">${escapeHtml(item.title)}</h6>
                  <p class="card-text text-muted small mb-2">${escapeHtml(item.description || 'No description provided.')}</p>
                  <div class="d-flex gap-3 text-muted small">
                    <span><i class="bi bi-file-earmark-pdf me-1"></i>${escapeHtml(item.file_name)}</span>
                    <span><i class="bi bi-hdd me-1"></i>${item.formatted_size || ''}</span>
                    <span><i class="bi bi-clock me-1"></i>${item.time_ago || ''}</span>
                  </div>
                </div>
                <div class="text-end flex-shrink-0">
                  <a href="${downloadUrl}" class="btn btn-sm btn-outline-primary" title="Download Document">
                    <i class="bi bi-download me-1"></i>Download
                  </a>
                </div>
              </div>
            </div>
          </div>
        `;
      }).join('');

      resultsContainer.innerHTML = html;
    }

    function renderError(msg) {
      resultsContainer.innerHTML = `
        <div class="alert alert-danger p-3" role="alert">
          <i class="bi bi-exclamation-triangle-fill me-2"></i>${escapeHtml(msg)}
        </div>
      `;
    }

    function escapeHtml(str) {
      if (!str) return '';
      return str.replace(/[&<>"']/g, function(m) {
        return {
          '&': '&amp;',
          '<': '&lt;',
          '>': '&gt;',
          '"': '&quot;',
          "'": '&#39;'
        }[m];
      });
    }

    searchInput.addEventListener('input', () => {
      clearTimeout(debounceTimer);
      debounceTimer = setTimeout(performSearch, 300);
    });

    if (subjectFilter) {
      subjectFilter.addEventListener('change', performSearch);
    }
  });
})();
