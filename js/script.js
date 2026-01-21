$('[data-toggle="popover"]').popover();
$('[data-toggle="tooltip"]').tooltip({ trigger: "hover" });
$('[data-toggle2="tooltip"]').tooltip({ trigger: "hover" });

$('[data-confirm]').on('click', function(e){

    if (!confirm($(this).data('confirm'))) {
        return false;
    }

    return true;
});

var $container = $('.grid');

$container.imagesLoaded( function(){
  $container.masonry({
    itemSelector : '.grid-item'
  });
});

$('[data-toggle-comment]').on('click', function(e){
    e.preventDefault();
    e.stopPropagation();
    $('.right-panel').toggleClass('opened');
});

$('[data-more-info]').on('click', function(e){
    e.preventDefault();
    e.stopPropagation();
});

$('[data-error-close]').on('click', function(e){
    e.preventDefault();
    e.stopPropagation();

    $('.error').remove();
});

$('[data-form-comment]').on('submit', function(e){
    var form = $(this);
    var url = form.attr('action');

    $.ajax({
        type: "POST",
        url: url,
        data: form.serialize(),
        success: function(data) {
            var objectId = form.find('[data-object-id]').val();
            form.parents('[data-comments]').find('[data-comments-list]').append(data);
            form.find('textarea').val('');
            $('[data-comments-count-' + objectId+']').html(parseInt($('[data-comments-count-'+objectId+']').html()) + 1);
        }
    });

    e.preventDefault();
})

$(document).on('click', '[data-delete-comment]', function(e){
    if (confirm('Supprimer ce commentaire ?')) {
        var comment = $(this).parents('[data-comment]');
        var url = $(this).attr('href');

        $.ajax({
            type: "GET",
            url: url,
            success: function(data) {
                var objectId = comment.parents('.modal').attr('data-object-id');
                comment.remove();
                $('[data-comments-count-' + objectId+']').html(parseInt($('[data-comments-count-'+objectId+']').html()) - 1);
            }
        });
    }

    e.preventDefault();
})

$('[data-toggle-notif]').click(function(e){
    e.preventDefault();

    $('[data-notif]').toggleClass('opened');

    var url = $(this).attr('href');

    $.ajax({
        type: "GET",
        url: url
    });
})

$(document).ready(function(){
    var newNotifs = $('.notification.new').length

    if (0 < newNotifs) {
        $('<span>').html(newNotifs).addClass('notif-number').appendTo('[data-toggle-notif]');

    }

    var hash = window.location.hash;
    $(hash).find('[data-show-modal]').click();

    $('[data-notif-link').click(function(e){
        var hash = '#' + $(this).attr('href').split("#")[1];
        $(hash).find('[data-show-modal]').click();
    })

    $("[data-object-url]").on('blur', function() {
        var url = $(this).val(); // Get the URL from the input field
    
        // Proceed only if a URL is provided
        if (url.length > 0) {
            // Clear previous values
            $('[data-object-name]').val('');
            $('[data-object-desc]').val('');
            $('[data-object-imgurl]').val('');
    
            var proxyUrl = 'https://cors-anywhere.herokuapp.com/';
            var targetUrl = url;
            $.ajax({
                url: proxyUrl + targetUrl,
                type: 'GET',
                // data: {pageUrl: url},
                success: function(response) {
                    console.log(response);
                    // Parse the JSON response from the PHP script
                    const metadata = response;
    
                    // Fill the input fields with the metadata
                    if (metadata.title) {
                        $('[data-object-name]').val(metadata.title);
                    }
    
                    if (metadata.description) {
                        $('[data-object-desc]').val(metadata.description);
                    }
    
                    if (metadata.image) {
                        $('[data-object-imgurl]').val(metadata.image);
                    }
                },
                error: function() {
                    alert('Error: Unable to fetch metadata.');
                }
            });
        }
    });
})

$('body').click(function(e){
    if (0 == $(e.target).parents('[data-notif]').length) {
        $('[data-notif]').removeClass('opened');
    }
})

$('[data-switch-gifted]').on('change', function(e){
    if ($(this).is(':checked')) {
        $('body').addClass('show-gifted');
        createCookie('listeKdoShowGift', '1', '365');
    } else {
        $('body').removeClass('show-gifted');
        createCookie('listeKdoShowGift', '0', '365');
    }
});

if (1 == accessCookie('listeKdoShowGift')) {
    $('[data-switch-gifted]').click();
}

autosize($('textarea'));

$('html').on('click', function(){
    $('.reaction-details').hide();
})

$('[data-reaction-list]').each(function(){
    bindReaction($(this));
})

$('[data-add-reaction]').each(function(){
    bindReactionClick($(this));
})

function bindReaction(elem)
{
    elem.on('mouseenter click', function(e){
        e.preventDefault();
        e.stopPropagation();
        $(this).parent().find('.reaction-details').show();
    })
}

function bindReactionClick(elem) {
    elem.on('click', function(e){
        e.preventDefault();
        e.stopPropagation();
    
        var $url = $(this).attr('href');
        var $parent = $(this).parents('.reaction-list-container');
    
        $.ajax({
            type: "GET",
            url: $url,
            success: function(data) {
                $parent.html(data);
    
                bindReaction($parent.find('[data-reaction-list]'));

                $parent.find('[data-toggle="tooltip"]').tooltip({ trigger: "hover" });

                $parent.find('[data-add-reaction]').each(function(){
                    bindReactionClick($(this));
                    $(this).tooltip({ trigger: "hover" });
                })
            }
        });
    })
}

$('.grid-item').on('mouseleave', function(){
    $(this).find('.reaction-details').hide();
})

function createCookie(cookieName,cookieValue,daysToExpire) {
    var date = new Date();
    date.setTime(date.getTime()+(daysToExpire*24*60*60*1000));
    document.cookie = cookieName + "=" + cookieValue + "; expires=" + date.toGMTString();
}

function accessCookie(cookieName) {
    var name = cookieName + "=";
    var allCookieArray = document.cookie.split(';');
    for (var i=0; i<allCookieArray.length; i++) {
        var temp = allCookieArray[i].trim();
        
        if (temp.indexOf(name)==0) {
            return temp.substring(name.length,temp.length);
        }
    }
    
    return "";
}

function onSignIn(googleUser) {
    var profile = googleUser.getBasicProfile();
    if (0 == accessCookie('listKdoGoogleAuth') || '' == accessCookie('listKdoGoogleAuth')) {
        $.ajax({
            type: "POST",
            url: 'actions/connectWithGoogle.php',
            data: {
                'id' : profile.getId(),
                'name' : profile.getName(),
                'imageUrl': profile.getImageUrl(),
                'email' : profile.getEmail()
            },
            success: function(data) {
                createCookie('listKdoGoogleAuth', '1', '365');
                document.location.reload();
            }
        });
    }
}

function signOut() {
    var auth2 = gapi.auth2.getAuthInstance();
    auth2.signOut().then(function () {
        createCookie('listKdoGoogleAuth', '0', '365');
    });
}
