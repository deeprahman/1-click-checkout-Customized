jQuery( document ).ready( function( $ ) {
    
    $( "select.one-click-select-address" ).unbind();
        var modal   = $( '#one-click-modal');
        var overlay = $( '#one-click-modal-overlay')
        jQuery("select.one-click-select-address").on('change', function(){
             var select_address = $('option:selected',this).val();
            //var updatedurl = updateQueryStringParameter(jQuery("a.gm-wocc-checkout-button.checkout-button").attr('href'), 'gm-wocc-shipping-type', select_address);
            //$("a.gm-wocc-checkout-button.checkout-button").attr('href', updatedurl);
        }); 

    /**
     * General Function and Variables
     */

    var modal   = $( '#one-click-modal'),
        close   = modal.find('.one-click-wacp-close'),
        overlay = $( '#one-click-modal-overlay'),
        add_hidden_value = function( button ) {
            button.one( 'click', function(){
                $('[name="_yith_wocc_one_click"]').val('is_one_click');
            });
        },
        add_new_address = function( select ) {

            select.on('change', function () {

                var s = $(this),
                    // if in loop set link href
                    button = s.closest( '.one-click-select-address' ).find( '.gm-wocc-checkout-button' );

                if( s.val() != 'add-new' ) {
                    button.attr( 'data-address', s.val() );
                    return false;
                }



                // add scrollbar
                if (typeof $.fn.perfectScrollbar != 'undefined') {
                    modal.find('.woocommerce').perfectScrollbar({
                        suppressScrollX: true
                    });
                }

                // open modal
                center_modal();
                $("#shipping_country option:selected").trigger("change");
                modal.addClass('open');
                overlay.addClass('open');

                modal.find('form').on('submit', function (ev) {

                    ev.preventDefault();

                    // get data
                    var form = $(this), 
                        container = modal.find('.woocommerce'),
                        param = form.serializeArray(),
                        button = form.find( 'input[type="submit"]' );

                    button.prop("disabled",true);
                    // add action and nonce
                    param.push(
                        { name: "action", value: 'yith_wocc_add_address' },
                        { name: "context", value: 'frontend' }
                    );

                    if( typeof $.fn.block != 'undefined' ) {
                        form.block({
                            message   : null,
                            overlayCSS: {
                                background: '#fff no-repeat center',
                                opacity   : 0.5,
                                cursor    : 'none'
                            }
                        });
                    }
                    var i = 0;
                    console.log(i++);
                    $.ajax({
                        url     : "http://wordpress-230971-705948.cloudwaysapps.com/wp-admin/admin-ajax.php",
                        data    : $.param(param),
                        dataType: 'json',
                        success : function (res) {

                            var newhtml = $(res.html);

                            if (res.error) {
                                // check if there are already error
                                var error = form.siblings('.woocommerce-error');

                                if (error.length) {
                                    error.replaceWith(newhtml)
                                } else {
                                    form.before(newhtml);
                                }

                                container.scrollTop(0);
                                container.perfectScrollbar('update');
                            }
                            else {
                                // remove old select option
                                s.find('option').remove();
                                // add new option and reset
                                s.append( $(res.html).find('option') );
                                s.val( res.key ).trigger('change');
                                hide_modal();
                                // clear form
                                form.trigger('reset');
                            }

                            form.unblock();
                            button.prop("disabled",false);
                        },
                        error: function(res){
                            form.unblock();
                            button.prop("disabled",false);
                        }
                    })
                });
            });
        },
        add_query_arg = function( link ) {

            link.on( 'click', function(){
                var href = $(this).attr('href'),
                    address = $(this).data('address');

                if( ! href || typeof address == 'undefined' ) {
                    return;
                }

                $(this).prop( 'href', href + '&_yith_wocc_select_address=' + address );
            })
        },
        hide_modal = function() {
            overlay.removeClass('open');
            modal.removeClass('open');

            $( document).trigger( 'yith_wocc_closed_modal' );
        },
        // center popup function
        center_modal = function () {
            modal.css({
                'left': '50%',
                'top': '50%',
                'margin-left': -modal.outerWidth() / 2,
                'margin-top': -modal.outerHeight() / 2
            });
        };

    /**
     * Add form by click button activate
     */
    $(document).on( 'click', '.yith-wocc-activate', function(ev){

        if( typeof yith_wocc === 'undefined' ) {
            return false;
        }

        ev.preventDefault();
        var t = $(this);

        if( typeof $.fn.block != 'undefined' ) {
            t.block({
                message   : null,
                overlayCSS: {
                    background: '#fff no-repeat center',
                    opacity   : 0.5,
                    cursor    : 'none'
                }
            });
        }

        $.ajax({
            url: yith_wocc.ajaxurl,
            data: {
                context: 'frontend',
                product_id: t.data('product_id'),
                is_loop: t.data('is_loop'),
                action: 'yith_wocc_add_address',
                _nonce: yith_wocc.nonce_load
            },
            dataType: 'html',
            success: function( res ) {

                var container = t.closest('.one-click-container');

                t.replaceWith( $(res).filter('.one-click-container').html() );
                //re init
                container.yith_wocc_init();
            }
        });
    });

    $.fn.yith_wocc_init = function(){

        // add class initialized
        $(this).addClass('initialized');

        var select = $('.one-click-select-address'),
            button = $('.gm-wocc-checkout-button');

        add_hidden_value( button );
        add_new_address( select );

        add_query_arg( button );

        if( typeof $.fn.select2 != 'undefined' ) {
            select.select2({
                placeholder: 'select an address'
            });
        }

        $(document).on( 'yith_wocc_closed_modal', function(){
            if( select.val() == 'add-new' ) {
                select.val('');
                select.trigger('change');
            }
        });

    };
    
    $( 'form.variations_form' ).on( 'show_variation', function ( ev, variation, purchasable ) {
        if( purchasable ) {
            $(this).find( 'button.yith-wocc-button').removeClass( 'disabled' );
        } else {
            $(this).find( 'button.yith-wocc-button').addClass( 'disabled' );
        }
    });

    $( 'form.variations_form' ).on( 'hide_variation', function ( ev, variation, purchasable ) {
        $(this).find( 'button.yith-wocc-button').addClass( 'disabled' );
    });

    $( document ).on( 'click', '.yith-wocc-button', function(){
        if ( $( this ).is('.disabled') ) {
            event.preventDefault();
        }
    });

    /**
     * START
     */
    $(document).find('.one-click-container').each(function(){
        $(this).yith_wocc_init();
    });

    // modal event
    overlay.on('click', function(ev){ ev.preventDefault(); hide_modal(); });
    close.on('click', function(ev){ ev.preventDefault(); hide_modal(); });

    $( window ).on( 'resize', center_modal );

    // compatibility with ajax navigation and infinite scrolling
    $(document).on( 'yith-wcan-ajax-filtered yith_infs_added_elem', function() {
        $(document).find('.yith-wocc-wrapper:not(.initialized)').each(function(){
            $(this).yith_wocc_init();
        });
    })

} );

function updateQueryStringParameter(uri, key, value) {
  var re = new RegExp("([?&])" + key + "=.*?(&|$)", "i");
  var separator = uri.indexOf('?') !== -1 ? "&" : "?";
  if (uri.match(re)) {
    return uri.replace(re, '$1' + key + "=" + value + '$2');
  }
  else {
    return uri + separator + key + "=" + value;
  }
}