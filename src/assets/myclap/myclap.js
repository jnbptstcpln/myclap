// Extensions
$('.fulgur-slideshow').slideshow();
$('.fulgur-load_more').load_more();
$('.fulgur-background_loading').background_loading();

var observer = new MutationObserver(function(mutations) {
    console.log(mutations)
});


var mutationObserver = new MutationObserver(function(mutations) {
    mutations.forEach(function(mutation) {
        if (mutation.type === "childList") {
            $.forEach(mutation.addedNodes, function(index, element) {
                if (element.nodeType === Node.ELEMENT_NODE) {
                    $('.fulgur-background_loading', $(element)).background_loading();
                }
            })
        }
    });
});
mutationObserver.observe(document.documentElement, {
    childList: true,
    subtree: true
});

$('#toggle-leftbar').on('click', function(event) {
    event.preventDefault();
    Layout = $('body > .layout');
    if (Layout.attr('data-leftbar') !== 'mini') {
        Layout.attr('data-leftbar', 'mini');
        if (window.innerWidth >= 800) {
            localStorage.setItem('leftbar', 'mini');
        }
    } else {
        Layout.attr('data-leftbar', 'full');
        if (window.innerWidth >= 800) {
            localStorage.setItem('leftbar', 'full');
        }
    }
});

$('#show-search').on('click', function(event) {
    event.preventDefault();
    $('body > .topbar').attr('data-search', 'show');
    $('body > .layout').attr('data-search', 'show');
});
$('#cancel-search').on('click', function(event) {
    event.preventDefault();
    $('body > .topbar').attr('data-search', null);
    $('body > .layout').attr('data-search', null);
});