/********************************************************* {COPYRIGHT-TOP} ***
 * Licensed Materials - Property of IBM
 * 5725-L30, 5725-Z22
 *
 * (C) Copyright IBM Corporation 2024, 2025
 *
 * All Rights Reserved.
 * US Government Users Restricted Rights - Use, duplication or disclosure
 * restricted by GSA ADP Schedule Contract with IBM Corp.
 ********************************************************** {COPYRIGHT-END} **/

/**
 * @file
 * Setup slider for the case studies
 */

(function ($, Drupal, drupalSettings) {

  Drupal.behaviors.productCaseStudySetup = {
    attach: function (context) {

      // Initialize case study slider for more than 3 cases
      initCaseStudySlider(context);
    }
  };

  /**
   * Initialize case study slider with pure JavaScript
   */
  function initCaseStudySlider(context) {
    const sliderWrappers = context.querySelectorAll('.case-study-slider-wrapper');
    
    sliderWrappers.forEach(function(wrapper) {
      // Check if already initialized
      if (wrapper.classList.contains('slider-initialized')) {
        return;
      }
      
      const caseCount = parseInt(wrapper.getAttribute('data-case-count'));
      
      // Only initialize slider if more than 3 cases
      if (caseCount <= 3) {
        return;
      }
      
      const track = wrapper.querySelector('.case-study-slider-track');
      const slides = wrapper.querySelectorAll('.case-study-slide');
      const dotsContainer = wrapper.querySelector('.case-study-slider-dots');
      const prevBtn = wrapper.querySelector('.case-study-slider-prev');
      const nextBtn = wrapper.querySelector('.case-study-slider-next');
      
      if (!track || slides.length === 0) {
        return;
      }
      
      // Check if RTL mode is enabled
      const isRTL = document.documentElement.getAttribute('dir') === 'rtl';
      
      let currentIndex = 0;
      let slidesToShow = getSlidesToShow();
      const totalSlides = slides.length;
      let maxIndex = Math.max(0, totalSlides - slidesToShow);
      
      // Get slides to show based on screen width
      function getSlidesToShow() {
        const width = window.innerWidth;
        if (width > 1200) {
          return 3;
        } else if (width > 768) {
          return 2;
        } else {
          return 1;
        }
      }
      
      // Update button visibility based on screen size
      function updateButtonVisibility() {
        const width = window.innerWidth;
        if (width <= 1200) {
          // Show buttons on medium screens and below (≤1200px)
          prevBtn.style.display = 'flex';
          nextBtn.style.display = 'flex';
        } else {
          // Hide buttons on large desktop (>1200px)
          prevBtn.style.display = 'none';
          nextBtn.style.display = 'none';
        }
      }
      
      // Create dots
      function createDots() {
        dotsContainer.innerHTML = '';
        for (let i = 0; i <= maxIndex; i++) {
          const dot = document.createElement('button');
          dot.classList.add('case-study-slider-dot');
          dot.setAttribute('aria-label', 'Go to slide ' + (i + 1));
          if (i === 0) {
            dot.classList.add('active');
          }
          dot.addEventListener('click', function() {
            goToSlide(i);
          });
          dotsContainer.appendChild(dot);
        }
      }
      
      createDots();
      
      // Update slider position - centers the middle card on screen
      function updateSlider() {
        const slideWidth = slides[0].offsetWidth;
        const gap = 48; // Total gap between cards (24px margin on each side)
        const wrapperWidth = wrapper.offsetWidth;
        
        // Calculate offset to center the current card
        // For desktop (3 cards): center the middle card of the visible set
        // For tablet (2 cards): center between the two cards
        // For mobile (1 card): center the single card
        
        let offset;
        if (slidesToShow === 3) {
          // Desktop: center the middle card (currentIndex + 1)
          const centerCardIndex = currentIndex + 1;
          const cardCenterPosition = centerCardIndex * (slideWidth + gap) + (slideWidth / 2);
          const wrapperCenter = wrapperWidth / 2;
          offset = -(cardCenterPosition - wrapperCenter);
        } else if (slidesToShow === 2) {
          // Tablet: center between two cards
          const firstCardIndex = currentIndex;
          const cardCenterPosition = firstCardIndex * (slideWidth + gap) + slideWidth + (gap / 2);
          const wrapperCenter = wrapperWidth / 2;
          offset = -(cardCenterPosition - wrapperCenter);
        } else {
          // Mobile: center single card
          const cardCenterPosition = currentIndex * (slideWidth + gap) + (slideWidth / 2);
          const wrapperCenter = wrapperWidth / 2;
          offset = -(cardCenterPosition - wrapperCenter);
        }
        
        // In RTL mode, invert the offset direction
        if (isRTL) {
          offset = -offset;
        }
        
        track.style.transform = 'translateX(' + offset + 'px)';
        
        // Update dots
        const currentDots = dotsContainer.querySelectorAll('.case-study-slider-dot');
        currentDots.forEach(function(dot, index) {
          if (index === currentIndex) {
            dot.classList.add('active');
          } else {
            dot.classList.remove('active');
          }
        });
        
        // Update button states
        updateButtonStates();
        updateButtonVisibility();
      }
      
      // Update button states (enabled/disabled)
      function updateButtonStates() {
        if (prevBtn && nextBtn) {
          if (isRTL) {
            prevBtn.disabled = currentIndex >= maxIndex;
            nextBtn.disabled = currentIndex === 0;
            
            prevBtn.style.opacity = currentIndex >= maxIndex ? '0.3' : '1';
            nextBtn.style.opacity = currentIndex === 0 ? '0.3' : '1';
          } else {
            prevBtn.disabled = currentIndex === 0;
            nextBtn.disabled = currentIndex >= maxIndex;
            
            prevBtn.style.opacity = currentIndex === 0 ? '0.3' : '1';
            nextBtn.style.opacity = currentIndex >= maxIndex ? '0.3' : '1';
          }
        }
      }
      
      // Go to specific slide
      function goToSlide(index) {
        currentIndex = Math.max(0, Math.min(index, maxIndex));
        updateSlider();
      }
      
      // Previous slide
      function prevSlide() {
        if (currentIndex > 0) {
          currentIndex--;
          updateSlider();
        }
      }
      
      // Next slide
      function nextSlide() {
        if (currentIndex < maxIndex) {
          currentIndex++;
          updateSlider();
        }
      }
      
      // Button click handlers
      if (prevBtn) {
        prevBtn.addEventListener('click', isRTL ? nextSlide : prevSlide);
      }
      if (nextBtn) {
        nextBtn.addEventListener('click', isRTL ? prevSlide : nextSlide);
      }
      
      // Keyboard navigation
      wrapper.addEventListener('keydown', function(e) {
        if (e.key === 'ArrowLeft') {
          isRTL ? nextSlide() : prevSlide();
        } else if (e.key === 'ArrowRight') {
          isRTL ? prevSlide() : nextSlide();
        }
      });
      
      // Touch/swipe support
      let touchStartX = 0;
      let touchEndX = 0;
      
      track.addEventListener('touchstart', function(e) {
        touchStartX = e.changedTouches[0].screenX;
      }, { passive: true });
      
      track.addEventListener('touchend', function(e) {
        touchEndX = e.changedTouches[0].screenX;
        handleSwipe();
      }, { passive: true });
      
      function handleSwipe() {
        const swipeThreshold = 50;
        const diff = touchStartX - touchEndX;
        
        if (Math.abs(diff) > swipeThreshold) {
          if (diff > 0) {
            isRTL ? prevSlide() : nextSlide();
          } else {
            isRTL ? nextSlide() : prevSlide();
          }
        }
      }
      
      // Handle window resize
      let resizeTimer;
      window.addEventListener('resize', function() {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(function() {
          // Recalculate slidesToShow and maxIndex
          slidesToShow = getSlidesToShow();
          maxIndex = Math.max(0, totalSlides - slidesToShow);
          
          // Reset to first slide if current index is out of bounds
          if (currentIndex > maxIndex) {
            currentIndex = maxIndex;
          }
          
          // Recreate dots for new maxIndex
          createDots();
          
          updateSlider();
        }, 250);
      });
      
      // Initial update
      updateSlider();
      
      // Mark as initialized
      wrapper.classList.add('slider-initialized');
    });
  }

})(jQuery, Drupal, drupalSettings);