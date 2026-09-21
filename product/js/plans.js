
(function ($, Drupal) {

  let planModalShowing = false;

  function togglePlanDetails(planIndex) {
    const modal = $(`.planDetail--modal_wrapper[data-planindex="${planIndex}"]`);

    planModalShowing = !planModalShowing
    modal.attr('aria-hidden', planModalShowing ? 'false' : 'true');
    modal.toggle();
  }

  function hasOpenTwistie(rows) {
    return rows.toArray().some((element) => !$(element).hasClass("hiddenRow"))
  }

  function openTwistieArrow(twistie) {
    if(!twistie.hasClass('flip')){
      twistie.addClass('flip');
    }
  }

  function closeTwistieArrow(twistie) {
    if(twistie.hasClass('flip')){
      twistie.removeClass('flip');
    }
  }

  function toggleTwistie(rowIndex) {
    const row = $(`[data-twistierowid="row_${rowIndex}"]`);
    row.toggleClass("hiddenRow");
    const twistie = $(`[data-twistieid="twistie_${rowIndex}"]`);
    twistie.toggleClass("flip");

    toggleHeaderTwistie();
  }

  function toggleHeaderTwistie() {
    const twistieHeader = $('[data-twistieheaderid="twistie_header"]')
    const rows = $('[data-twistierowid*="row_"]');
    if (hasOpenTwistie(rows)) {
      openTwistieArrow(twistieHeader)
    } else {
      closeTwistieArrow(twistieHeader)
    }
  }

  function toggleAllTwisties() {
    const rows = $('[data-twistierowid*="row_"]');
    if(hasOpenTwistie(rows)) {
      closeTwisties();
    } else {
      openTwisties();
    }
    toggleHeaderTwistie();
  }

  function openTwisties() {
    const rows = $('[data-twistierowid*="row_"]');
    rows.each(function() {
      if($(this).hasClass('hiddenRow')){
        $(this).removeClass('hiddenRow');
      }
    });

    const twisties = $('[data-twistieid*="twistie_"]');
    twisties.each(function() {
      openTwistieArrow($(this));
    });
  }

  function closeTwisties() {
    const rows = $('[data-twistierowid*="row_"]');
    rows.each(function() {
      if(!$(this).hasClass('hiddenRow')){
        $(this).addClass('hiddenRow')
      }
    })

    const twisties = $('[data-twistieid*="twistie_"]');
    twisties.each(function() {
      closeTwistieArrow($(this));
    });

    const twistieHeader = $('[data-twistieheaderid="twistie_header"]');
    closeTwistieArrow(twistieHeader);
  }

  function handleApiSelection(apiName, productId, planIndex, resultsContainer) {
    let basePath = '/';
    if (typeof drupalSettings !== 'undefined' && drupalSettings.path) {
      if (drupalSettings.path.pathPrefix) {
        basePath = '/' + drupalSettings.path.pathPrefix;
      } else if (drupalSettings.path.baseUrl) {
        basePath = drupalSettings.path.baseUrl;
      }
    }
    if (basePath === '/' && window.location.pathname.includes('/product/')) {
      const pathParts = window.location.pathname.split('/product/');
      basePath = pathParts[0] + '/';
    }
    
    const url = `${basePath}product/${productId}/api-details/${encodeURIComponent(apiName)}?planIndex=${encodeURIComponent(planIndex)}`;
    
    resultsContainer.html('<p class="loading-message">Loading API details...</p>');
    
    $.ajax({
      url: url,
      method: 'GET',
      dataType: 'text',
      success: function(encoded) {
        // Decode base64 response
        const html = atob(encoded);
        resultsContainer.html(html);
        console.log(resultsContainer);
        if (typeof Drupal !== 'undefined' && Drupal.attachBehaviors) {
          Drupal.attachBehaviors(resultsContainer[0]);
        }
      },
      error: function(xhr, status, error) {
        console.error('Error loading API details:', status, error);
        console.error('Response:', xhr.responseText);
        resultsContainer.html('<p class="error">Error loading API details. Check console for details.</p>');
      }
    });
  }

  function searchProductApis(searchTerm, productId, resultsContainer) {
    // Trim whitespace from search term
    searchTerm = searchTerm.trim();

    const planIndex = resultsContainer.closest('.api-search-container').find('.plan-api-search').data('plan-index');

    // Make AJAX request to search all product APIs
    // Use pathPrefix if available, otherwise construct from current location
    let basePath = '/';
    if (typeof drupalSettings !== 'undefined' && drupalSettings.path) {
      if (drupalSettings.path.pathPrefix) {
        basePath = '/' + drupalSettings.path.pathPrefix;
      } else if (drupalSettings.path.baseUrl) {
        basePath = drupalSettings.path.baseUrl;
      }
    }
    // Fallback: extract base path from current URL
    if (basePath === '/' && window.location.pathname.includes('/product/')) {
      const pathParts = window.location.pathname.split('/product/');
      basePath = pathParts[0] + '/';
    }
    
    const url = `${basePath}product/${productId}/search-apis?title=${encodeURIComponent(searchTerm)}`;
    console.log('Search URL:', url);
    
    $.ajax({
      url: url,
      method: 'GET',
      dataType: 'text',
      success: function(encoded) {
        // Decode base64 response
        const html = atob(encoded);
        resultsContainer.html(html);
        
        // Add plan index and product ID to the rendered list items
        resultsContainer.find('.contained-list-item--clickable').each(function() {
          const $item = $(this);
          $item.attr('data-plan-index', planIndex);
          $item.attr('data-product-id', productId);
          
          const apiName = $item.data('api-name');
          
          $item.on('click', function() {
            handleApiSelection(apiName, productId, planIndex, resultsContainer);
          });
        });
        
        // Attach Drupal behaviors if available
        if (typeof Drupal !== 'undefined' && Drupal.attachBehaviors) {
          Drupal.attachBehaviors(resultsContainer[0]);
        }
      },
      error: function(xhr, status, error) {
        console.error('Search error:', status, error);
        console.error('Response:', xhr.responseText);
        resultsContainer.html('<p class="error">Error searching APIs. Check console for details.</p>');
      }
    });
  }

  let api = {
    togglePlanDetails,
    toggleTwistie,
    closeTwisties,
    toggleAllTwisties,
    searchProductApis
  };
  if (!Drupal.settings) {
    Drupal.settings = {};
  }
  Drupal.settings.plans = api;

  Drupal.behaviors.plansTable = {
    attach: function(context, settings) {
      $('#twistieHeader', context).off('click.plansTable').on('click.plansTable', function() {
        toggleAllTwisties();
      });

      $('.planDetail--modalClose', context).off('click.plansTable').on('click.plansTable', function() {
        closeTwisties();
      });

      $('.rowTwistie', context).off('click.plansTable').on('click.plansTable', function() {
        var index = $(this).data('rowid');
        toggleTwistie(index);
      });

      $('.api-search-apply-btn', context).off('click.plansTable').on('click.plansTable', function() {
        const searchInput = $(this).closest('.input-group').find('.plan-api-search');
        const searchTerm = searchInput.val();
        const searchSource = searchInput.data('search-source');
        
        if (searchSource === 'product-apis') {
          const productId = $(this).data('product-id');
          const resultsContainer = $(this).closest('.api-search-container').find('.search-results');
          searchProductApis(searchTerm, productId, resultsContainer);
        }
      });
    }
  };
})(jQuery, Drupal);
