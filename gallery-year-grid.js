(function (global) {
  'use strict';

  function escapeHtml(text) {
    if (text == null) return '';
    var div = document.createElement('div');
    div.textContent = String(text);
    return div.innerHTML;
  }

  function stripTags(html) {
    if (!html) return '';
    var d = document.createElement('div');
    d.innerHTML = String(html);
    return (d.textContent || d.innerText || '').trim();
  }

  function itemTimestamp(item) {
    var keys = ['date', 'created_at', 'event_date', 'achievement_date', 'award_date', 'received_date', 'captured_at'];
    for (var i = 0; i < keys.length; i++) {
      var v = String(item[keys[i]] || '').trim();
      if (!v || v === '0000-00-00' || v === '0000-00-00 00:00:00') continue;
      var t = Date.parse(v);
      if (!isNaN(t)) return t;
    }
    return 0;
  }

  function matchesFilter(item, galleryType, subType) {
    if (galleryType) {
      var gt = String(item.gallery_type || '').toLowerCase();
      if (gt !== String(galleryType).toLowerCase()) return false;
    }
    if (subType) {
      var st = item.gallery_sub_type && item.gallery_sub_type.sub_type_name;
      if (st !== subType) return false;
    }
    return true;
  }

  function formatDisplayDate(item) {
    var keys = ['date', 'created_at', 'event_date', 'achievement_date', 'award_date', 'received_date'];
    for (var i = 0; i < keys.length; i++) {
      var raw = item[keys[i]];
      if (!raw) continue;
      var v = String(raw).trim();
      if (!v || v === '0000-00-00' || v === '0000-00-00 00:00:00') continue;
      var ts = Date.parse(v);
      if (!isNaN(ts) && ts > 0) {
        var d = new Date(ts);
        return {
          year: String(d.getFullYear()),
          month: d.toLocaleString('en', { month: 'short' }),
          day: String(d.getDate()).padStart(2, '0'),
        };
      }
    }
    return { year: '—', month: '', day: '' };
  }

  function renderMediaCard(item, cardOpts) {
    var pdfLabel = cardOpts.pdfLabel;
    var mediaCountLabel = cardOpts.mediaCountLabel;
    var categoryText = cardOpts.categoryText;
    var heading = item.heading || 'Untitled';
    var id = item.id != null ? String(item.id) : '';
    var media = Array.isArray(item.media) ? item.media : [];
    var first = media[0];
    var url = first && first.media_url ? String(first.media_url) : '';
    var isPdf = false;
    if (url) {
      var ext = url.split('.').pop().toLowerCase().split('?')[0];
      if (ext === 'pdf') isPdf = true;
    }
    var previewUrl = !isPdf && url ? url : 'https://via.placeholder.com/400x300?text=No+Media';
    var fd = formatDisplayDate(item);
    var cat =
      categoryText != null
        ? categoryText
        : item.gallery_type
          ? String(item.gallery_type).charAt(0).toUpperCase() + String(item.gallery_type).slice(1)
          : 'Gallery';

    var thumbBlock;
    if (isPdf) {
      thumbBlock =
        '<div class="pdf-preview-card">' +
        '<svg class="w-16 h-20 text-red-600 mb-2" fill="currentColor" viewBox="0 0 24 24">' +
        '<path d="M14 2H6c-1.1 0-1.99.9-1.99 2L4 20c0 1.1.89 2 1.99 2H18c1.1 0 2-.9 2-2V8l-6-6zm2 14h-3v3h-2v-3H8v-2h3v-3h2v3h3v2z"/>' +
        '</svg>' +
        '<div class="text-sm font-medium text-gray-700">' +
        escapeHtml(pdfLabel) +
        '</div>' +
        '<div class="absolute top-2 right-2 bg-red-600 text-white text-xs font-bold px-2 py-1 rounded">PDF</div>' +
        '</div>';
    } else {
      thumbBlock =
        '<img class="rounded-t-lg w-full h-[200px] object-cover" src="' +
        escapeHtml(previewUrl) +
        '" alt="' +
        escapeHtml(stripTags(heading)) +
        '">';
    }

    return (
      '<div class="gallery-item w-full mx-auto bg-white border border-gray-200 rounded-lg ' +
      'shadow hover:shadow-[rgba(0,0,0,0.15)_0px_15px_25px,rgba(0,0,0,0.05)_0px_5px_10px] ' +
      'transition-shadow duration-300">' +
      '<a href="gallery-detail?id=' +
      encodeURIComponent(id) +
      '" class="block">' +
      thumbBlock +
      '</a>' +
      '<div class="sm:p-4 p-1 flex flex-col justify-between relative">' +
      '<a href="gallery-detail?id=' +
      encodeURIComponent(id) +
      '">' +
      '<div class="flex gap-4">' +
      '<div class="w-[30%]">' +
      '<div class="bg-blue-main text-white text-center rounded-t-lg p-1 font-[700] text-[18px]">' +
      escapeHtml(fd.year) +
      '</div>' +
      '<div class="text-center font-[700] text-[24px] text-[#D9A414] rounded-b-lg border border-gray-300">' +
      escapeHtml(fd.day) +
      '<br><span class="text-[#223B71] text-[14px]">' +
      escapeHtml(fd.month) +
      '</span></div></div>' +
      '<div class="w-[70%]">' +
      '<div class="text-blue-main text-[1rem] font-[700] m-2 line-clamp-2">' +
      escapeHtml(stripTags(heading)) +
      '</div><hr>' +
      '<div class="flex gap-2 text-[9px] text-[#3B3B3B] m-2">' +
      '<div>Category: <strong>' +
      escapeHtml(cat) +
      '</strong></div>' +
      '<div>' +
      escapeHtml(mediaCountLabel) +
      ': <strong>' +
      media.length +
      '</strong></div></div></div></div></a>' +
      '<a href="gallery-detail?id=' +
      encodeURIComponent(id) +
      '">' +
      '<button type="button" class="group py-1 px-4 sm:px-6 rounded-[10px] w-full border border-gray text-blue-main hover:text-white hover:bg-[#003618] flex gap-2 items-center justify-center mt-5">' +
      'View More' +
      '<svg class="w-[14px] h-[10px] fill-[#223B71] group-hover:fill-white" width="8" height="9" viewBox="0 0 8 9" xmlns="http://www.w3.org/2000/svg">' +
      '<path fill-rule="evenodd" clip-rule="evenodd" d="M6.65008 0.911564C6.9831 0.911564 7.25307 1.18153 7.25307 1.51456L7.25307 6.63112C7.25307 6.96414 6.9831 7.23411 6.65008 7.23411C6.31705 7.23411 6.04708 6.96414 6.04708 6.63112L6.04708 2.97031L1.10714 7.91026C0.871652 8.14574 0.489858 8.14574 0.254375 7.91026C0.0188919 7.67477 0.018892 7.29298 0.254376 7.0575L5.19432 2.11755L1.53352 2.11755C1.20049 2.11755 0.930523 1.84758 0.930523 1.51456C0.930523 1.18153 1.20049 0.911564 1.53352 0.911564L6.65008 0.911564Z"></path>' +
      '</svg></button></a></div></div>'
    );
  }

  function sortItems(arr) {
    return arr.slice().sort(function (a, b) {
      return itemTimestamp(b) - itemTimestamp(a);
    });
  }

  function init(userOpts) {
    var opts = {
      branchId: 9,
      galleryType: 'gallery',
      subType: null,
      gridSelector: '#galleryGrids',
      yearSelectSelector: '#galleryYearSelect',
      searchSelector: '#searchInput',
      prevBtnId: 'photoPrevBtn',
      nextBtnId: 'photoNextBtn',
      pageNumbersId: 'photoPageNumbers',
      paginationId: 'photoPagination',
      noResultsSelector: '#noResults',
      itemsPerPage: 6,
      emptyMessage: 'No items for this year.',
      loadingMessage: 'Loading…',
      searchEmptyMessage: 'No matching items found.',
      pdfLabel: 'PDF Document',
      mediaCountLabel: 'Total Media',
      categoryText: null,
      apiBase: 'proxy/gallery-proxy',
    };
    for (var k in userOpts) {
      if (Object.prototype.hasOwnProperty.call(userOpts, k)) opts[k] = userOpts[k];
    }
    // Kalyanpur: JWT proxy + branch 9
    opts.branchId = 9;
    opts.apiBase = 'proxy/gallery-proxy';

    var grid = document.querySelector(opts.gridSelector);
    var yearSel = document.querySelector(opts.yearSelectSelector);
    var searchInput = document.querySelector(opts.searchSelector);
    var prevBtn = document.getElementById(opts.prevBtnId);
    var nextBtn = document.getElementById(opts.nextBtnId);
    var pageNumbersEl = document.getElementById(opts.pageNumbersId);
    var paginationEl = document.getElementById(opts.paginationId);
    var noResultsEl = document.querySelector(opts.noResultsSelector);

    if (!grid || !yearSel) return;

    var allItems = [];
    var filteredItems = [];
    var currentPage = 1;
    var cardOpts = {
      pdfLabel: opts.pdfLabel,
      mediaCountLabel: opts.mediaCountLabel,
      categoryText: opts.categoryText,
    };

    function applyFilterAndRender() {
      var term = searchInput && searchInput.value ? searchInput.value.toLowerCase().trim() : '';
      filteredItems = allItems.filter(function (item) {
        var h = stripTags(item.heading || '').toLowerCase();
        return !term || h.includes(term);
      });
      currentPage = 1;
      renderPage();
    }

    function renderPage() {
      var total = filteredItems.length;
      var totalPages = Math.ceil(total / opts.itemsPerPage) || 0;

      if (total === 0) {
        var searching = searchInput && searchInput.value.trim();
        if (allItems.length > 0 && searching) {
          grid.innerHTML =
            '<p class="col-span-full text-center text-gray-500 py-10">' +
            escapeHtml(opts.searchEmptyMessage) +
            '</p>';
        } else {
          grid.innerHTML =
            '<p class="col-span-full text-center text-gray-500 py-10">' +
            escapeHtml(opts.emptyMessage) +
            '</p>';
        }
        if (paginationEl) paginationEl.style.display = 'none';
        if (noResultsEl) {
          if (allItems.length > 0 && searching) noResultsEl.classList.remove('hidden');
          else noResultsEl.classList.add('hidden');
        }
        return;
      }

      if (noResultsEl) noResultsEl.classList.add('hidden');
      if (paginationEl) paginationEl.style.display = 'flex';

      if (currentPage > totalPages) currentPage = totalPages;
      var start = (currentPage - 1) * opts.itemsPerPage;
      var slice = filteredItems.slice(start, start + opts.itemsPerPage);
      grid.innerHTML = slice.map(function (item) {
        return renderMediaCard(item, cardOpts);
      }).join('');

      updatePaginationUI(totalPages);
    }

    function updatePaginationUI(totalPages) {
      if (!prevBtn || !nextBtn || !pageNumbersEl) return;
      prevBtn.disabled = currentPage <= 1;
      nextBtn.disabled = currentPage >= totalPages || totalPages <= 1;
      pageNumbersEl.innerHTML = '';
      for (var i = 1; i <= totalPages; i++) {
        (function (p) {
          var pageBtn = document.createElement('button');
          pageBtn.className =
            'px-3 py-2 ' +
            (p === currentPage ? 'bg-blue-main text-white' : 'bg-gray-200 text-gray-700') +
            ' rounded hover:bg-gray-300';
          pageBtn.textContent = String(p);
          pageBtn.addEventListener('click', function () {
            currentPage = p;
            renderPage();
          });
          pageNumbersEl.appendChild(pageBtn);
        })(i);
      }
    }

    function loadYear(year) {
      grid.innerHTML =
        '<p class="col-span-full text-center text-gray-500 py-10">' +
        escapeHtml(opts.loadingMessage) +
        '</p>';
      var url =
        opts.apiBase +
        '?branch=' +
        encodeURIComponent(opts.branchId) +
        '&year=' +
        encodeURIComponent(year);
      fetch(url)
        .then(function (res) {
          return res.json();
        })
        .then(function (json) {
          if (!json || !json.success || !Array.isArray(json.data)) {
            allItems = [];
            applyFilterAndRender();
            return;
          }
          allItems = sortItems(
            json.data.filter(function (item) {
              return matchesFilter(item, opts.galleryType, opts.subType);
            })
          );
          applyFilterAndRender();
        })
        .catch(function () {
          grid.innerHTML =
            '<p class="col-span-full text-center text-red-600 py-10">Unable to load galleries.</p>';
          allItems = [];
          if (paginationEl) paginationEl.style.display = 'none';
        });
    }

    yearSel.addEventListener('change', function () {
      currentPage = 1;
      loadYear(yearSel.value);
    });

    if (searchInput) {
      searchInput.addEventListener('input', function () {
        applyFilterAndRender();
      });
    }

    if (prevBtn) {
      prevBtn.addEventListener('click', function () {
        if (currentPage > 1) {
          currentPage--;
          renderPage();
        }
      });
    }
    if (nextBtn) {
      nextBtn.addEventListener('click', function () {
        var totalPages = Math.ceil(filteredItems.length / opts.itemsPerPage) || 1;
        if (currentPage < totalPages) {
          currentPage++;
          renderPage();
        }
      });
    }

    loadYear(yearSel.value);
  }

  global.DPSGalleryYearGrid = { init: init };
})(typeof window !== 'undefined' ? window : this);
