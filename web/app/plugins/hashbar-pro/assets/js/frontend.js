;(function ($) {
    "use strict";

    $.fn.get_transparent_selector = function get_transparent_selector(){
        let transparent_header_selector = $(this).closest('.hthb-notification.hthb-pos--top[data-transparent_header_selector]').last().data('transparent_header_selector');
        if($(this).closest('.hthb-notification.hthb-pos--top').hasClass('hthb-transparent')){
            return transparent_header_selector;
        }

        return false;
    }

    $.fn.add_transparent_header_spacing = function add_transparent_header_spacing(top_notification_height){
        if( $('body').find('#wpadminbar').length ){
            top_notification_height = Number.parseInt(top_notification_height + $('body').find('#wpadminbar').height());
        }
    }

    function calculate_top_zero(){
        if( $('body').find('#wpadminbar').length ){
            return '32px';
        } else{
            return '0px';
        }
    }

    var timeout = 400;
    $(window).on('load',function(){
        var top_notification_height         = Number.parseInt($('.hthb-notification.hthb-pos--top').last().height()),
            bottom_notification_height      = $('.hthb-notification.hthb-pos--bottom').last().height(),
            left_wall_notification_width    = $('.hthb-notification.hthb-pos--left-wall').last().width(),
            right_wall_notification_width   = $('.hthb-notification.hthb-pos--right-wall').last().width();

        var position;

        // if load as minimized disabled
        $('.hthb-notification.hthb-state--open').each(function(){
            position = $(this).getPostion();

            if( position == 'top' ){
                $('body').addClass('hthb hthb-pt--' + top_notification_height );
                if( $(this).get_transparent_selector() ){
                    if(window.getComputedStyle(document.querySelector($(this).get_transparent_selector())).getPropertyValue("position") !== 'sticky') {
                        $($(this).get_transparent_selector()).addClass('hthb-top-unset');
                        $($(this).get_transparent_selector()).css( {'top':'unset'} );
                    } else {
                        $($(this).get_transparent_selector()).addClass('hthb-top-'+top_notification_height);
                        $($(this).get_transparent_selector()).css( {'top':top_notification_height+'px'} );
                    }
                }
            } else if( position == 'bottom'){
                $('body').css('padding-bottom', bottom_notification_height + 'px');
            }

            // Implement how many time to show
            var time_to_show           = $(this).data('time_to_show'),
                id                     = $(this).data('id') ? '_' + $(this).data('id') : '', 
                coockie_count          = Cookies.get('hashbarpro_cookiecount' + id),
                hashbarpro_oldcookie   = Cookies.get('hashbarpro_oldcookie' + id);

            if( time_to_show && time_to_show > 0 ){

                if(document.cookie.indexOf("hashbarpro_oldcookie" + id) >= 0){
                    if(time_to_show == hashbarpro_oldcookie){
                        coockie_count++;
                        Cookies.set('hashbarpro_cookiecount' + id, coockie_count, { expires: 7 });
                    } else {
                        Cookies.set('hashbarpro_oldcookie' + id, time_to_show, { expires: 7 });
                        Cookies.set('hashbarpro_cookiecount' + id, 1, { expires: 7 });
                    }
                } else {
                    Cookies.set('hashbarpro_oldcookie' + id, time_to_show, { expires: 7 });
                    Cookies.set('hashbarpro_cookiecount' + id, 1, { expires: 7 });
                }
            }

            if( coockie_count > time_to_show ){
               $(this).css({'display': 'none'});
               $(this).removeClass('hthb-state--open').addClass('hthb-state--minimized');

               if( position == 'top' ){
                    $('body').removeClass('hthb');
               }
            }
            
        });

        // Load as mimimized if option is set to minimized
        $('.hthb-notification.hthb-state--minimized').each(function(){
             var position = $(this).getPostion();
             if( position == 'top' || position == 'top-promo' || position == 'bottom' || position == 'bottom-promo' ){
                $(this).find('.hthb-row').css('display', 'none');
             }
            
        });

        // Left/right wall
        $('.hthb-notification.hthb-state--minimized').each(function(){
            var position = $(this).getPostion();
            if( position == 'left-wall' ){
                  $(this).css('left', '-' + left_wall_notification_width + 'px' );
            } else if( position == 'right-wall' ){
                 $(this).css('right', '-' + right_wall_notification_width + 'px' );
            }
           
        });

        setTimeout(function(){
            $('.hthb-notification').addClass('hthb-loaded');
        }, timeout );


        $('.hthb-notification').each(function() {
            const delay_time = $(this).attr('data-delay_time');
            if(+delay_time) {
                setTimeout(() => {
                    $(this).showNotification( top_notification_height, bottom_notification_height, left_wall_notification_width, right_wall_notification_width );
                }, +delay_time*1000);
            }
        })

        // When click close button
        $('.hthb-close-toggle').on('click', function(e){
            const $notification = $(this).closest('.hthb-notification');
            const $id = e.currentTarget.dataset.id;
            let $expire_time = +hashbar_localize.cn_cookies_expire_time
            if(hashbar_localize.cn_cookies_expire_type === 'hours') {
                $expire_time = $expire_time/24;
            }
            if(hashbar_localize.cn_cookies_expire_type === 'minutes') {
                $expire_time = $expire_time/1440;
            }

            $(this).minimizeNotification( top_notification_height, bottom_notification_height, left_wall_notification_width, right_wall_notification_width);

            // Mark as manually closed to prevent scroll-based reopening
            $notification.addClass('hthb-manually-closed');

            // Keep closed
            if( hashbar_localize.bar_keep_closed == '1' ){
                Cookies.set(`keep_closed_bar_${$id}`, '1', { expires: $expire_time, path: '/' });
            }

            // Don't show forever
            if( hashbar_localize.dont_show_bar_after_close == '1' ) {
                Cookies.set( `dont_show_bar_${$id}`, '1', { expires: $expire_time, path: '/' } );
            }
        });

        // When clicked open button
        $('.hthb-open-toggle').on('click', function(){
            // Remove manually-closed class to allow reopening
            $(this).closest('.hthb-notification').removeClass('hthb-manually-closed');
            $(this).showNotification( top_notification_height, bottom_notification_height, left_wall_notification_width, right_wall_notification_width );
        });

        // Enter / Space on custom controls (role="button" on <span>)
        $('.hthb-close-toggle, .hthb-open-toggle').on('keydown', function (e) {
            if (e.key !== 'Enter' && e.key !== ' ') {
                return;
            }
            e.preventDefault();
            $(this).trigger('click');
        });

        // When scroll position matched with a notification
        // Show the notifications
        var window_inner_height             = $(window).height(),
            page_height                     = $('body').height();

        let current_scroll_position         = window.pageYOffset || document.documentElement.scrollTop,
            current_scroll_position_percent = current_scroll_position / scroll_pos_max * 100;
            current_scroll_position_percent = Number.parseInt(current_scroll_position_percent);

        var scroll_pos_max = $(document).height() - $(window).height();

        $(window).on('scroll', function(e){
            current_scroll_position         = $(window).scrollTop(),
            current_scroll_position_percent = current_scroll_position / scroll_pos_max * 100;
            current_scroll_position_percent = Number.parseInt(current_scroll_position_percent);

            $(`.hthb-scroll`).each(function(){
                let scroll_to_show = $(this).data('scroll_to_show'),
                    scroll_to_hide = $(this).data('scroll_to_hide');

                $(this).trigger_notification_on_scroll(scroll_to_show, scroll_to_hide, current_scroll_position, scroll_pos_max);
            });
        });
    });
    
    /**
     * Calculate % of a given value.
     * For example, If the give value is 1000 & we want to know the 75% of it.
     * It will return 750 as the result.
     *
     * @param number percent_amount, % amount.
     * @param number percent_of, Total amount from where the % should be calculated.
     * @return number
     */
    function percent_of(percent_amount, percent_of){
        percent_of = Number.parseInt(percent_of);
        percent_amount = Number.parseInt(percent_amount);
        
        return percent_of * percent_amount / 100;
    }

    $.fn.getPostion = function(){
        if(this.closest('.hthb-notification').hasClass('hthb-pos--top')){
            return 'top';
        }

        if(this.closest('.hthb-notification').hasClass('hthb-pos--bottom')){
            return 'bottom';
        }

        if(this.closest('.hthb-notification').hasClass('hthb-pos--left-wall')){
            return 'left-wall';
        }

        if(this.closest('.hthb-notification').hasClass('hthb-pos--right-wall')){
            return 'right-wall';
        }

        if(this.closest('.hthb-notification').hasClass('hthb-pos--bottom-promo')){
            return 'bottom-promo';
        }

        if(this.closest('.hthb-notification').hasClass('hthb-pos--top-promo')){
            return 'top-promo';
        }
    }

    $.fn.minimizeNotification = function(top_notification_height, bottom_notification_height, left_wall_notification_width, right_wall_notification_width){
        var postion = this.getPostion(),
            left_wall_notification_width = this.closest('.hthb-notification').width(),
            right_wall_notification_width = this.closest('.hthb-notification').width();

        this.closest('.hthb-notification').removeClass('hthb-state--open');
        this.closest('.hthb-notification').addClass('hthb-state--minimized');

        if(postion != 'left-wall' && postion != 'right-wall'){
            // Use slideUp instead of slideToggle to prevent double-toggle issue
            this.closest('.hthb-notification').find('.hthb-row').slideUp();
        }

        if( postion == 'top' ){
            $('body').removeClass('hthb');

            // for sticky
            if( this.get_transparent_selector() ){
                $(this.get_transparent_selector()).addClass('hthb-top-unset');
                $(this.get_transparent_selector()).css({'top': calculate_top_zero()});
            }
        } else if( postion == 'bottom' ){
            $('body').css('padding-bottom', '');
        } else if( postion == 'left-wall' ){
            this.closest('.hthb-notification').css('left', '-' + left_wall_notification_width + 'px' );
        } else if( postion == 'right-wall' ){
            this.closest('.hthb-notification').css('right', '-' + right_wall_notification_width + 'px' );
        }
    }

    $.fn.showNotification = function(top_notification_height, bottom_notification_height, left_wall_notification_width, right_wall_notification_width){
        // Don't reopen if user manually closed the notification
        if( this.closest('.hthb-notification').is('.hthb-manually-closed') ){
            return;
        }

        var postion = this.getPostion(),
        left_wall_notification_width = this.closest('.hthb-notification').width(),
        right_wall_notification_width = this.closest('.hthb-notification').width();

        this.closest('.hthb-notification').removeClass('hthb-state--minimized');
        this.closest('.hthb-notification').addClass('hthb-state--open');

        if(postion != 'left-wall' && postion != 'right-wall'){
            // Use slideDown instead of slideToggle to prevent double-toggle issue
            this.closest('.hthb-notification').find('.hthb-row').slideDown();
        }

        if( postion == 'top' ){
            $('body').addClass('hthb hthb-pt--'+ top_notification_height );

            // for sticky
            if( this.get_transparent_selector() ){
                $(this.get_transparent_selector()).addClass('hthb-top-unset');
                $(this.get_transparent_selector()).css({'top': 'unset'});
            }
        } else if( postion == 'bottom' ){
            $('body').css('padding-bottom', bottom_notification_height + 'px');
        } else if( postion == 'left-wall' ){
            this.closest('.hthb-notification').css('left', '');
        } else if( postion == 'right-wall' ){
            this.closest('.hthb-notification').css('right', '');
        }
    }

    $.fn.trigger_click_on_open_button = function(){
        // Don't reopen if user manually closed the notification
        if( $(this).is('.hthb-manually-closed') ){
            return;
        }
        $(this).addClass('hthb-trigger-open-clicked').removeClass('hthb-trigger-close-clicked');
        $(this).find('.hthb-open-toggle').trigger('click');
    }

    $.fn.trigger_click_on_close_button = function(){
        $(this).removeClass('hthb-trigger-open-clicked').addClass('hthb-trigger-close-clicked');
        $(this).find('.hthb-close-toggle').trigger('click');
    }

    $.fn.trigger_notification_on_scroll = function( scroll_to_show, scroll_to_hide, current_scroll_position, scroll_pos_max ){
        // Don't reopen if user manually closed the notification
        if( $(this).is('.hthb-manually-closed') ){
            return;
        }

        if( (scroll_to_show && typeof scroll_to_show == 'string' && scroll_to_show.indexOf('%')) > 0 && (scroll_to_hide && typeof scroll_to_hide == 'string' && scroll_to_hide.indexOf('%')) > 1 ){
            scroll_to_show = Number.parseInt(scroll_to_show);
            scroll_to_hide = Number.parseInt(scroll_to_hide);
            // 20% ,  80%
            // console.log(1,scroll_to_show,scroll_to_hide,current_scroll_position, percent_of(scroll_to_hide, scroll_pos_max));

            if(current_scroll_position > percent_of(scroll_to_show, scroll_pos_max) &&  current_scroll_position < percent_of(scroll_to_hide, scroll_pos_max) ){
                if( !$(this).is('.hthb-state--open') && !$(this).is('.hthb-trigger-open-clicked') ){
                    $(this).trigger_click_on_open_button(); // show
                }
            } else{
                if( !$(this).is('.hthb-state--minimized') ){
                    $(this).trigger_click_on_close_button(); // hide
                }
            }
        } else if( (scroll_to_show && typeof scroll_to_show == 'string' && scroll_to_show.indexOf('%')) &&  (scroll_to_hide === '' || scroll_to_hide == undefined) ){
            scroll_to_show = Number.parseInt(scroll_to_show);
            // 20% , ''/undefined
            // console.log(2,scroll_to_show,scroll_to_hide,current_scroll_position, percent_of(scroll_to_hide, scroll_pos_max));

            if( current_scroll_position > percent_of(scroll_to_show, scroll_pos_max) ){
                if( !$(this).is('.hthb-state--open') && !$(this).is('.hthb-trigger-open-clicked') ){
                    $(this).trigger_click_on_open_button(); // show
                }
            } else {
                if( !$(this).is('.hthb-state--minimized') ){
                    $(this).trigger_click_on_close_button(); // hide
                }
            }
        } else if( (scroll_to_show && typeof scroll_to_show == 'number') && (scroll_to_hide && typeof scroll_to_hide == 'string')){
            // 300 , 80%
            // console.log(3,scroll_to_show,scroll_to_hide,current_scroll_position, percent_of(scroll_to_hide, scroll_pos_max));

            if( current_scroll_position > scroll_to_show &&  current_scroll_position < percent_of(scroll_to_hide, scroll_pos_max) ){
                if( !$(this).is('.hthb-state--open') && !$(this).is('.hthb-trigger-open-clicked') ){
                    $(this).trigger_click_on_open_button(); // show
                }
            } else{
                if( !$(this).is('.hthb-state--minimized') ){
                    $(this).trigger_click_on_close_button(); // hide
                }
            }

        } else if( (scroll_to_show === '' || scroll_to_show == undefined) && (scroll_to_hide && typeof scroll_to_hide == 'string' && scroll_to_hide.indexOf('%')) ){
            scroll_to_hide = Number.parseInt(scroll_to_hide);
            // empty / undefined , 90%
            // console.log(4,scroll_to_show,scroll_to_hide,current_scroll_position, percent_of(scroll_to_hide, scroll_pos_max));

            if( current_scroll_position > percent_of(scroll_to_hide, scroll_pos_max) ){
                if( !$(this).is('.hthb-state--minimized') ){
                    $(this).trigger_click_on_close_button(); // hide
                }
            } else{
                if( !$(this).is('.hthb-state--open') && !$(this).is('.hthb-trigger-open-clicked') ){
                    $(this).trigger_click_on_open_button(); // show 
                }
            }

        } else if( (scroll_to_show && typeof scroll_to_show == 'number') && (scroll_to_hide === '' || scroll_to_hide == undefined) ){
            // 300 , empty/undefined 
            // console.log(5,scroll_to_show,scroll_to_hide,current_scroll_position, percent_of(scroll_to_hide, scroll_pos_max));
            if( current_scroll_position < scroll_to_show ){
                if( !$(this).is('.hthb-state--minimized') ){
                    $(this).trigger_click_on_close_button(); // hide
                }

            } else{
                if( !$(this).is('.hthb-state--open') && !$(this).is('.hthb-trigger-open-clicked') ){
                    $(this).trigger_click_on_open_button(); // show
                }
            }
            
        } else {
            console.log(`Invalid formate  Scroll to show=${typeof scroll_to_show} ${scroll_to_show } scroll_to_hide=${typeof scroll_to_hide} ${scroll_to_hide}`);
        }
    }

    $(document).ready(function(){
        $(".hthb-countdown").each(function(){
            var countdown_id = "#"+$(this).attr('id')+" .hthb-countdown-section .hthb-countdown-wrap",
                finalDate    = $(countdown_id).data('countdown'),
                customLabel  = $(countdown_id).data('custom_label');
            $(countdown_id).countdown(finalDate, function (event) {
                $(countdown_id+' .countdown-day').html(event.strftime('%D'));
                $(countdown_id+' .countdown-day-text').html(customLabel.day);
                $(countdown_id+' .countdown-hour').html(event.strftime('%H'));
                $(countdown_id+' .countdown-hour-text').html(customLabel.hour);
                $(countdown_id+' .countdown-minute').html(event.strftime('%M'));
                $(countdown_id+' .countdown-minite-text').html(customLabel.min);
                $(countdown_id+' .countdown-second').html(event.strftime('%S'));
                $(countdown_id+' .countdown-second-text').html(customLabel.sec);
            });
        });

        // Initialize new announcement bar countdowns
        initializeNewAnnouncementBars();
    });

    /**
     * Initialize new announcement bars with countdown timers
     */
    function initializeNewAnnouncementBars() {
        var bars = document.querySelectorAll('.hashbar-announcement-bar');
        if (bars.length === 0) {
            return;
        }

        bars.forEach(function(bar) {
            if (bar.getAttribute('data-countdown-enabled') === 'true') {
                initializeCountdown(bar);
            }
        });
    }

    /**
     * Initialize countdown timer
     */
    function initializeCountdown(bar) {
        // Prevent multiple initializations on the same bar
        if (bar.hasAttribute('data-countdown-initialized')) {
            return;
        }
        bar.setAttribute('data-countdown-initialized', 'true');

        var countdownType = bar.getAttribute('data-countdown-type');
        var countdownDate = bar.getAttribute('data-countdown-date');

        // Find the bar wrapper (parent of the bar if it exists)
        var barWrapper = bar.parentElement && bar.parentElement.classList.contains('hashbar-announcement-bar-wrapper')
            ? bar.parentElement
            : bar;

        // Query all countdown timers in the wrapper (could be before, inline, after, or below)
        var timerElements = barWrapper.querySelectorAll('.hashbar-countdown-timer');

        if (timerElements.length === 0 || !countdownDate) {
            return;
        }

        // Update all countdown timers immediately and then every second
        timerElements.forEach(function(timerElement) {
            updateCountdown(timerElement, countdownDate, countdownType);
        });

        // Store the interval ID on the bar for potential cleanup
        var intervalId = setInterval(function() {
            timerElements.forEach(function(timerElement) {
                updateCountdown(timerElement, countdownDate, countdownType);
            });
        }, 1000);

        bar.setAttribute('data-countdown-interval', intervalId);
    }

    /**
     * Update countdown display
     */
    function updateCountdown(timerElement, countdownDate, countdownType) {
        var now = new Date().getTime();
        var countDate = new Date(countdownDate).getTime();
        var distance = countDate - now;

        if (distance < 0) {
            timerElement.textContent = 'Ended';
            return;
        }

        var days = Math.floor(distance / (1000 * 60 * 60 * 24));
        var hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
        var minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
        var seconds = Math.floor((distance % (1000 * 60)) / 1000);

        // Get display options from element attributes
        var showDays = timerElement.getAttribute('data-show-days') !== 'false';
        var showHours = timerElement.getAttribute('data-show-hours') !== 'false';
        var showMinutes = timerElement.getAttribute('data-show-minutes') !== 'false';
        var showSeconds = timerElement.getAttribute('data-show-seconds') !== 'false';

        // Determine style (simple, digital, circular)
        var style = timerElement.getAttribute('data-countdown-style') || 'simple';

        if (style === 'simple') {
            // Simple text format: "5d 3h 45m 30s"
            var parts = [];
            if (showDays && days > 0) parts.push(days + 'd');
            if (showHours && hours > 0) parts.push(hours + 'h');
            if (showMinutes && minutes > 0) parts.push(minutes + 'm');
            if (showSeconds) parts.push(seconds + 's');

            timerElement.textContent = parts.length > 0 ? parts.join(' ') : '0s';
        } else if (style === 'digital') {
            // Digital format: "05:03:45:30"
            var displayText = '';
            if (showDays) displayText += padZero(days) + ':';
            if (showHours) displayText += padZero(hours) + ':';
            if (showMinutes) displayText += padZero(minutes) + ':';
            if (showSeconds) displayText += padZero(seconds);

            // Remove trailing colon if no seconds are shown
            timerElement.textContent = displayText.replace(/:$/, '');
        } else if (style === 'circular' || style === 'box') {
            // Circular or box format - update boxes
            updateBoxCountdown(timerElement, days, hours, minutes, seconds, showDays, showHours, showMinutes, showSeconds);
        } else if (countdownType === 'compact') {
            // Legacy compact format
            if (days > 0) {
                timerElement.textContent = days + 'd ' + hours + 'h';
            } else if (hours > 0) {
                timerElement.textContent = hours + 'h ' + minutes + 'm';
            } else {
                timerElement.textContent = minutes + 'm ' + seconds + 's';
            }
        } else {
            // Legacy detailed format
            timerElement.textContent = padZero(days) + ':' + padZero(hours) + ':' + padZero(minutes) + ':' + padZero(seconds);
        }
    }

    /**
     * Update box-style countdown display (circular or rounded squares)
     */
    function updateBoxCountdown(timerElement, days, hours, minutes, seconds, showDays, showHours, showMinutes, showSeconds) {
        // Update days box (works with both .countdown-circle-box and .countdown-box classes)
        if (showDays) {
            var daysBox = timerElement.querySelector('.countdown-days');
            if (daysBox) {
                daysBox.textContent = String(days).padStart(2, '0');
            }
        }

        // Update hours box
        if (showHours) {
            var hoursBox = timerElement.querySelector('.countdown-hours');
            if (hoursBox) {
                hoursBox.textContent = String(hours).padStart(2, '0');
            }
        }

        // Update minutes box
        if (showMinutes) {
            var minutesBox = timerElement.querySelector('.countdown-minutes');
            if (minutesBox) {
                minutesBox.textContent = String(minutes).padStart(2, '0');
            }
        }

        // Update seconds box
        if (showSeconds) {
            var secondsBox = timerElement.querySelector('.countdown-seconds');
            if (secondsBox) {
                secondsBox.textContent = String(seconds).padStart(2, '0');
            }
        }
    }

    /**
     * Pad number with leading zero
     */
    function padZero(num) {
        return (num < 10 ? '0' : '') + num;
    }
})(jQuery);