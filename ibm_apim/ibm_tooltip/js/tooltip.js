
/********************************************************* {COPYRIGHT-TOP} ***
  * Licensed Materials - Property of IBM
  * 5725-L30, 5725-Z22
  *
  * (C) Copyright IBM Corporation 2025
  *
  * All Rights Reserved.
  * US Government Users Restricted Rights - Use, duplication or disclosure
  * restricted by GSA ADP Schedule Contract with IBM Corp.
********************************************************** {COPYRIGHT-END} **/
(function (Drupal) {
    const Elements = {
      GET: 'element-1',
      POST: 'element-2',
      PUT: 'element-3',
      PATCH: 'element-4',
      DELETE: 'element-5',
      OPTIONS: 'element-6',
    }
    Drupal.behaviors.consoleMessage = {
      attach: function (context, settings) {
        if (window.myScriptInitialized) return;
        window.myScriptInitialized = true;
        const data = drupalSettings.ibm_tooltip?.sharedData;

        document.addEventListener('mouseover', function (event) {
          const target = event.target.closest('[tooltip-id^="node-"]');
          if (target) {
            const nodeId = target.getAttribute('tooltip-id');
            if(data[nodeId]) {
              showTooltip(data[nodeId]);
              positionTooltip(target);
            } else {
              hideTooltip();
            }
          } else {
            hideTooltip();
          }
        });

        let tooltip = document.getElementById('apic-tooltip');
        if (!tooltip) {
          tooltip = document.createElement('div');
          tooltip.id = 'apic-tooltip';
          tooltip.classList.add('apic-tooltip-pointer');
          tooltip.classList.add('apic-tooltip-pointer-left');
          document.body.appendChild(tooltip);
        }

        function positionTooltip(target) {
          if (!tooltip || !tooltip.parentElement || !target) return null;
          const margin = 10;
          const targetRect = target.getBoundingClientRect();
          const ttWidth = tooltip.offsetWidth;
          const ttHeight = tooltip.offsetHeight;
          const targetTop = targetRect.top + window.scrollY;
          const targetLeft = targetRect.left + window.scrollX;
          const targetRight = targetRect.right + window.scrollX;
          const targetCenterY = targetRect.top + targetRect.height / 2 + window.scrollY;
          let left, top;
          let renderedRight = true;
          if (targetRect.right + ttWidth + margin < window.innerWidth) {
            left = targetRight + margin;
            renderedRight = true;
          } else {
            left = targetLeft - ttWidth - margin;
            renderedRight = false;
            if (left < window.scrollX + margin) {
              left = window.scrollX + margin;
            }
          }
          top = targetCenterY - ttHeight / 2;
          const maxTop = window.scrollY + window.innerHeight - ttHeight - margin;
          if (top < window.scrollY + margin) {
            top = window.scrollY + margin;
          } else if (top > maxTop) {
            top = maxTop;
          }
          tooltip.style.left = `${left}px`;
          tooltip.style.top = `${top}px`;
          setPointerDirection(renderedRight);
        }

        function setPointerDirection(isRight) {
          if (isRight) {
            tooltip.classList.add('apic-tooltip-pointer-right');
            tooltip.classList.remove('apic-tooltip-pointer-left');
          } else {
            tooltip.classList.add('apic-tooltip-pointer-left');
            tooltip.classList.remove('apic-tooltip-pointer-right');
          } 
        }
  
        function showTooltip(dataArray) {
          const rowsHtml = dataArray.map(item => {
            const key = item.key.toUpperCase();
            item.color = item?.color || getElementById(key);
            const tagClass = `apic-tooltip-tag-${item.color}`;
            let rows = item.summary.map(row => {
              return `<div class="apic-tooltip-cell">${row}</div>`;
            }).join('');
            rows = item.summary.length != 0 ? rows : ['<div class="apic-tooltip-cell">' + Drupal.t('Description is not provided') + '</div>'];
            return `
                <div class="apic-tooltip-cell">
                  <span class="apic-tooltip-tag ${tagClass}"> ${key} </span>
                </div>
                ${rows}
                <div class="apic-tooltip-divider"></div>
            `;
          }).join('');
        
          const html = `
            <div>
              <div class="apic-tooltip-grid apic-tooltip-grid-${dataArray[0].summary.length == 0 ? 2 : dataArray[0].summary.length + 1}">
                ${rowsHtml}
              </div>
            </div>
            <div class="apic-tooltip-footer">${Drupal.t('Open to view more')}</div>
          `;
          tooltip.innerHTML = html;
          tooltip.style.opacity = '1';
        }
  
        function hideTooltip() {
          tooltip.style.opacity = '0';
        }

        function getElementById(key) {
          if (key && Elements[key]) {
            return Elements[key];
          } else {
            const values = Object.values(Elements);
            const randomIndex = Math.floor(Math.random() * values.length);
            return values[randomIndex];
          }
        }
      }
    };
  })(Drupal);
  