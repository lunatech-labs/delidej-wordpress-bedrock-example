(function($) {
  "use strict";

    $(document).ready(function() {
        $('.tooltip').tooltipster({
            theme: 'tooltipster-light',
            contentCloning: true,
            // trigger: 'click', // used to style the tooltip content
        });
        var countdown_init_style = $('.hthb-countdown-style-demo select').val();
        countdownStyleDisplay(countdown_init_style);
        $('.hthb-countdown-style-demo select').on('change',function(){
        	countdownStyleDisplay($(this).val());
        });

        function countdownStyleDisplay(countdown_style){
        	var ptagtest 	   = $('.hthb-countdown-style-demo .csf-fieldset').find('.countdown-style-img'),
        		selected_style = countdown_style;

        	if(!selected_style){
        		$('.countdown-style-img').html('');
        		return;
        	}

        	if(ptagtest.length == 0){
        		$('.hthb-countdown-style-demo .csf-fieldset').append('<div class="countdown-style-img"><img src="'+hashbar_admin.hashbar_plugin_uri+'/admin/img/'+selected_style+'.png" alt="style-image"></div>');
        	}else{
        		$('.countdown-style-img').html('<img src="'+hashbar_admin.hashbar_plugin_uri+'/admin/img/'+selected_style+'.png" alt="style-image">');
        	}
        }

        // CHange notification display option to open when the user change position to top/bottom promo.
        $('[name="_wphash_[_wphash_notification_position]"]').on('change', function() {
            if($(this).val() === 'ht-n_toppromo' || $(this).val() === 'ht-n_bottompromo') {
                $('[name="_wphash_[_wphash_notification_display]"]').val("ht-n-open");
            }
        })

    });

})(jQuery);

document.addEventListener('DOMContentLoaded', function() {
    // Debounce function
    function debounce(func, wait) {
        let timeout;
        return function() {
            clearTimeout(timeout);
            timeout = setTimeout(() => func.apply(this, arguments), wait);
        };
    }

    // Function to check block
    function isBlockInUse(blocksName) {
        const blocks = wp.data.select('core/block-editor').getBlocks();

        return blocks.some(block => {
            if (blocksName.includes(block.name)) {
                return true;
            }
            if (block.innerBlocks && block.innerBlocks.length) {
                return block.innerBlocks.some(innerBlock => blocksName.includes(innerBlock.name));
            }
            return false;
        });
    }

    // Toggle meta box with state tracking
    let previousState = null;
    function toggleMetaBox() {
        const metaBoxes = document.querySelectorAll('[value="ht-n_toppromo"], [value="ht-n_bottompromo"]');
        if (metaBoxes) {
            const bannerList = ['hashbar/hashbar-promo-banner-image', 'hashbar/hashbar-promo-banner']
            const isPromoBannerUsed = isBlockInUse(bannerList);
            
            // Only update if state changed
            if (previousState !== isPromoBannerUsed) {
                metaBoxes.forEach(metabox => {
                    if(isPromoBannerUsed) {
                        metabox.closest('li').style.removeProperty("display");
                    } else {
                        metabox.closest('li').style.display = "none";
                    }
                    document.querySelectorAll(`[data-value="${metabox.value}"]`).forEach(item => {
                        if(isPromoBannerUsed) {
                            item.style.removeProperty("display");
                        } else {
                            item.style.display = "none";
                        }
                    });
                });
                // Correct way to set checked attribute
                if(!isPromoBannerUsed) {
                    const defaultRadio = document.querySelector('[value="ht-n-top"]');
                    if (defaultRadio) {
                        defaultRadio.checked = true;
                        document.querySelectorAll(`[data-value="${defaultRadio.value}"]`).forEach(item => {
                            item.style.removeProperty("display");
                            item.classList.remove('csf-depend-on');
                        });
                    }
                }
                previousState = isPromoBannerUsed;
            }
        }
    }

    // Debounced version of toggle
    const debouncedToggle = debounce(toggleMetaBox, 250);

    // Subscribe to editor changes
    wp.data.subscribe(() => {
        if (wp.data.select('core/editor')) {
            debouncedToggle();
        }
    });
});