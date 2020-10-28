// Extensions
$('.fulgur-buttons-navigation').buttons_navigation();
$('.fulgur-search_table').search_table();
$('.fulgur-search_item').search_item();
$('.fulgur-autocomplete').autocomplete();
$('.fulgur-fileinput').fileinput();
$('.fulgur-keywords_input').keywords_input();
$('.fulgur-background_loading').background_loading();

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