/**
 * SLIDESHOW
 */
$.registerElementExtension('slideshow', function() {
    var container = this;
    var slides = container.children();
    if (slides.length() <= 1) {
        return;
    }
    var slides_container = $.DOM.create('div').addClass('slides-container');
    var indicators_container = $.DOM.create('div').addClass('indicators-container');

    // Save the container original height
    container.height(container.height());

    slides.each(function(index, el) {

        // Remove element from the DOM
        el.remove();
        // Set attribute and append to the slides container
        el.attr('data-id', index);
        slides_container.append(el);
        indicators_container.append('<a data-id="{0}"></a>'.format(index));
    });
    container
        .append(slides_container)
        .append(indicators_container)
    ;


    function activate(index) {
        container.find('*.active').toCollection().each(function(index, el) {el.removeClass('active')});
        container.find('*[data-id="{0}"]'.format(index)).each(function(index, el) {el.addClass('active')});
    }

    indicators_container.find('a').on('click', function(event) {
        event.preventDefault();
        activate(event.element.attr('data-id'));
    });

    // Store the interval
    var interval = null;
    function planTransition() {
        interval = setInterval(function() {
            Index = parseInt(slides_container.find('.active').attr('data-id'));
            activate((Index+1)%slides.length());
        }, 5000);
    }
    planTransition();


    // Disable the transition when the user is over the slideshow
    container.on('mouseover', function(event) {
        clearInterval(interval);
    });
    // Re-enable it when he leaves
    container.on('mouseout', function(event) {
        planTransition();
    });

    // Activate the first element
    activate(0);
    container.addClass('ready');
});


/**
 * BUTTONS NAVIGATION
 */
$.registerElementExtension('buttons_navigation', function(active) {
    var buttons_container = this;
    var panels = [];

    if (!active) {
        var url = window.location.href, idx = url.indexOf("#");
        active = idx != -1 ? url.substring(idx+1) : undefined;
    }

    // Hide all except the active
    buttons_container.find('a.button').each(function(index, el) {
        var id = el.attr('href').split('#').join('');
        panels.push(id);
        el.on('click', function (event) {
            event.preventDefault();
            if (!event.element.hasClass('active')) {
                var id = event.element.attr('href').split('#').join('');
                document.location.href = document.location.href.replace(/#.*$/, "")+"#"+id;
                show(id);
            }
        });
    });

    function show(active_id) {
        for (var i in panels) {
            var id = panels[i];
            var panel = $($.format('#{0}', id));
            var button = $($.format('a[href="#{0}"]', id));

            if (id === active_id) {
                panel.show('active');
                button.addClass('active');
            } else {
                panel.hide();
                button.removeClass('active');
            }

        }
    }

    if (!active || panels.indexOf(active) < 0) {
        active = panels[0];
    }
    show(active);
});


/**
 * SEARCH TABLE
 */
$.registerElementExtension('search_table', function() {
    var input = this;
    var exclude_selector = input.attr('data-exclude-selector');
    var id = input.attr('data-table-id');
    if (!id) {
        console.warn('Vous devez spécifier le tableau sur lequel effectuer la rechercher (data-table-id="")');
        return
    }
    var table = $('#{0}'.format(id));
    if (!table.exists()) {
        console.warn('Le tableau "{0}" n\'existe pas'.format('#{0}'.format(id)));
        return;
    }
    var tbody = table.find('tbody');
    if (!tbody.exists()) {
        console.warn('Le tableau "{0}" ne possède pas d\'enfant <tbody>'.format('#{0}'.format(id)));
        return;
    }

    var timeout = null;
    input.on('input', function(event) {
        var value = input.get('value');
        if (timeout) { clearTimeout(timeout) }
        timeout = setTimeout(function() {
            if (value.length > 0) {
                var row_headers = [];
                tbody.find('tr').toCollection().each(function(index, element) {
                    if (!exclude_selector || !element.matches(exclude_selector)) {
                        data_search = element.attr('data-search') || "";
                        if (element.text().hasSubString(value) || data_search.hasSubString(value)) {
                            var row_header_id = element.attr('data-row-header-id');
                            if (row_header_id) {
                                if (row_headers.indexOf(row_header_id) < 0) {
                                    row_headers.push(row_header_id);
                                }
                            }
                            element.show();
                        } else {
                            element.hide();
                        }
                    }
                });
                $.forEach(row_headers, function(i, row_header) {
                    var el =  $('#{0}'.format(row_header));
                    if (el) {
                        el.show();
                    }
                })
            } else {
                tbody.find('tr').show();
            }
        }, 300);
    });
});


/**
 * SEARCH ITEM
 */
$.registerElementExtension('search_item', function() {
    var container = this;
    var url = container.attr('data-url');
    var placeholder = container.attr('data-placeholder') || "Rechercher...";
    var no_match = container.attr('data-nomatch') || "Aucun élément ne correspond à votre recherche";
    var item_width = container.attr('data-itemwidth') || 250;
    var item_format = container.attr('data-itemformat') || "<h3>{label}</h3>";


    container.append('<div><input class="fullwidth" placeholder="{placeholder}"></div><div class="grid items" style="--item-width: {item_width}px;"></div>'.format( {
        placeholder: placeholder,
        item_width: item_width
    }));

    var input = container.find('input');
    var grid = container.find('.grid');

    var input_timeout = null;
    input.on('input', function(event) {
        value = event.element.get('value');
        if (input_timeout) { clearTimeout(input_timeout) }
        if (value.length > 0) {
            input_timeout = setTimeout(function() {
                grid.removeClass('items');
                grid.html('<div class="fullwidth h-100 layout center"><i class="far fa-spinner fa-spin"></i></div>');
                $.api.get(url, {value: value}, function(rep) {
                    if (rep.success) {
                        render(rep.payload);
                    } else {
                        grid.html('<div class="gradient-red fullwidth h-100 layout center"><h4><i class="far fa-exclamation-circle"></i> Une erreur est survenue</h4></div>');
                    }
                })
            }, 500);
        } else {
            grid.html('');
        }
    });

    function render(payload) {
        grid.html('');
        grid.addClass('items');
        for (i in payload) {
            item = payload[i];
            grid.append( ('<a class="item h-100 layout center" href="{href}">'+item_format+'</a>').format(item));
        }
        if (payload.length === 0) {
            grid.removeClass('items');
            grid.append( ('<a class="item h-100 layout center"><h3>{0}</h3></a>').format(no_match));
        }
    }
});

/**
 * AUTOCOMPLETE
 */
$.registerElementExtension('autocomplete', function() {

    var input = this;
    input
        .style('margin-bottom', '0')
    ;
    var parent = input.parent();
    var url = input.attr('data-url');
    var values = input.attr('data-values'); // Séparer par "+"
    var label_name = input.attr('data-label');
    var loading_mode = input.attr('data-loading-mode') || 'stored'; // "stored" or "fetch"
    var data = [];

    var container = $.DOM.create('div').addClass('fulgur-autocomplete');
    // Remove input from the DOM
    input.remove();
    // Append input to the container
    container.append(input);
    // Append the container to input's previous parent
    parent.append(container);

    var options = $.DOM.create('ul');
    container.append(options);

    // Init data
    if (loading_mode === "stored" && url) {
        $.api.get(url, {}, function(response) {
            if (response.success) {
                data = response.payload;
            }
        });
    } else if (values) {
        data = values.split('+');
    }

    function render_loading() {
        options.html('<li><i class="far fa-spinner fa-spin"></i> Chargement...</li>');
        var nb_options = options.children().length();
        options.get('style').setProperty('--nb-options', nb_options);
        open();
    }

    function render_error() {
        options.html('<li><i class="far fa-exclamation-circle"></i> Une erreur est survenue...</li>');
        var nb_options = options.children().length();
        options.get('style').setProperty('--nb-options', nb_options);
        open();
    }

    function render(value) {
        options.html('');
        if (value.length > 0) {
            for (i in data) {
                var label = "";
                if (typeof data[i] === 'string') {
                    label = data[i];
                } else {
                    label = data[i]['label'];
                }
                if (label.hasSubString(value)) {
                    var li = $.DOM.create('li');
                    li
                        .text(label)
                        .attr('data-label', label)
                    ;
                    options.append(li);
                }
            }
            var nb_options = options.children().length();
            options.get('style').setProperty('--nb-options', nb_options);
            if (nb_options > 0) {
                open();
            } else {
                close()
            }

        } else {
            close();
        }
    }

    function open() {
        container.addClass('open');
    }

    function close() {
        container.removeClass('open');
    }

    options.on('click', 'li', function(event) {
        close();
        input.trigger('focus');
        input.set('value', event.element.attr('data-label'));
        input.trigger('autocompleted');
    });

    options.on('mouseover', 'li', function(event) {
        Element = event.element;
        if (!Element.matches('.active')) {
            options.find('li.active').removeClass('active');
            Element.addClass('active');
        }
    });

    var search_timeout = null;
    input.on('input', function(event) {
        var value = input.get('value');
        if (loading_mode === "fetch") {
            if (search_timeout) {clearTimeout(search_timeout)}
            search_timeout = setTimeout(function() {
                render_loading();
                $.api.get(url, {value: value}, function(response) {
                    if (response.success) {
                        data = response.payload;
                        render(value);
                    } else {
                        render_error()
                    }
                });
            }, 400);
        } else {
            render(value);
        }

    });

    input.on('blur', function(e) {
        setTimeout(close, 150);
    });

    input.on('keydown', function(e) {
        if (e.keyCode === 40) { // ArrowDown
            e.preventDefault();
            ActiveLi = options.find('li.active');
            if (ActiveLi.exists()) {
                options.find('li.active').removeClass('active');
                $(ActiveLi._DOMElement.nextSibling).addClass('active');
            } else {
                options.find('li:first-child').addClass('active');
            }
        }
        if (e.keyCode === 38) { // ArrowUp
            e.preventDefault();
            ActiveLi = options.find('li.active');
            if (ActiveLi.exists()) {
                options.find('li.active').removeClass('active');
                $(ActiveLi._DOMElement.previousSibling).addClass('active');
            } else {
                options.find('li:first-child').addClass('active');
            }
        }
        if (e.keyCode === 13) { // Enter
            e.preventDefault();
            ActiveLi = options.find('li.active');
            if (ActiveLi.exists()) {
                close();
                input.trigger('focus');
                input.set('value', ActiveLi.attr('data-label'));
                input.trigger('autocompleted');
            }
        }
    })

});


/**
 * FILEINPUT
 */
$.registerElementExtension('fileinput', function () {
    var input = this;
    var parent = input.parent();
    var identifier_name = input.attr('data-identifier') || "";
    var input_identifier = $('input[name="{0}"]'.format(identifier_name));

    if (!input_identifier.exists()) {
        input_identifier = $.DOM.create('input');
    }

    var container = $.DOM.create('div').addClass('fulgur-fileinput');
    container
        .addClass('layout')
        .addClass('center')
    ;
    var display = $.DOM.create('div');
    container.append(display);

    // Remove input from the DOM
    input.remove();
    // Append input to the container
    container.append(input);
    input.hide();
    // Append the container to input's previous parent
    parent.append(container);

    function render_default_state() {
        display.html('');
        if (input_identifier.get('value').length > 0) {
            if (input.attr('data-href')) {
                display.append('<a class="button primary" href="{0}">Consulter</a>'.format($(input.attr('data-href').format(input_identifier.get('value')))));
            }
            display.append('<a class="button primary input" href="#">Modifier</a>');
        } else {
            display.append('<a class="button primary input" href="#">Sélectionner un fichier</a>');
        }
    }

    function render_input_state() {
        display.html('');
        console.log(display.html());
        var file_label = input.get('files').item(0).name;
        display.append('<h6>Fichier à envoyer :</h6>');
        display.append('<p>{0}</p>'.format(file_label));
        display.append('<div><a class="button small cancel">Annuler</a></div>')
    }

    container.on('click', 'a.input', function(event) {
        event.preventDefault();
        input.show();
        input._DOMElement.click();
        input.hide();
    });

    container.on('click', 'a.cancel', function(event) {
        event.preventDefault();
        input.set('value', '');
        render_default_state();
    });

    input.on('change', function(event) {
        if (input.get('files').length > 0) {
            render_input_state();
        } else {
            render_default_state();
        }
    });

    container.on('dragover', function(event) {
        event.preventDefault();
    });
    container.on('drop', function(event) {
        event.preventDefault();
        if (event.dataTransfer.items) {
            if (event.dataTransfer.items.length > 1) {
                alert("Vous ne pouvez ajouter qu'un seul fichier");
            } else {
                if (event.dataTransfer.items[0].kind === 'file') {
                    input.set('files', event.dataTransfer.files);
                }
            }
        } else {
            // Use DataTransfer interface to access the file(s)
            if (event.dataTransfer.files.length > 1) {
                alert("Vous ne pouvez ajouter qu'un seul fichier");
            } else {
                input.set('files', event.dataTransfer.files);
            }

        }

    });

    render_default_state();
});


/**
 * KEYWORDS_INPUT
 */
$.registerElementExtension('keywords_input', function() {
    var container = this.parent();
    var keywords = [];
    var input_hidden = this;
    var input_display = $.DOM.create('input')
        .attr('placeholder', 'Ajouter un élément...')
        .addClass('small')
        .attr('data-url', input_hidden.attr('data-url'))
        .attr('data-label', 'label')
    ;

    function add() {
        var keyword = input_display.get('value').trim();
        if (keyword.length > 0) {
            if (keywords.indexOf(keyword) < 0) {
                keywords.push(keyword);
                update();
                label_container.append(render(keyword));
            }
            input_display.set('value', '');
        }
    }

    input_display.on('keydown', function(event) {
        if (event.keyCode === 13) {
            event.preventDefault();
            add();
        }
    });
    input_display.on('autocompleted', function(event) {
        add();
    });

    var label_container = $.DOM.create('span').addClass('fulgur-keywords_input-container');
    label_container.on('click', 'span.delete', function(event) {
        var label =  event.element.parent('span.label');
        var keyword = label.attr('data-value');
        console.log(keyword, keywords.indexOf(keyword));
        if (keywords.indexOf(keyword) >= 0) {
            keywords.splice(keywords.indexOf(keyword), 1);
            update();
        }
        label.remove();
    });

    try {
        keywords = JSON.parse(input_hidden.get('value'));
    } catch (e) {}

    container.append(label_container);
    container.append(input_display);

    function update() {
        input_hidden.set('value', JSON.stringify(keywords));
    }

    function render(keyword) {
        return $.format('<span class="label mb-5" data-value="{0}">{0} <span class="delete"><i class="fas fa-times-circle"></i></span></span>', keyword);
    }

    function draw() {
        label_container.html('');
        for (i in keywords) {
            label_container.append(render(keywords[i]));
        }
    }

    input_display.autocomplete();

    container.find('div.fulgur-autocomplete').style('max-width', '200px');

    draw();
});

/**
 * LOAD MORE
 */
$.registerElementExtension('load_more', function() {
    var container = this;
    var video_row = null;

    var url = container.attr('data-url');
    var init = container.attr('data-init') || 4;
    var step = container.attr('data-step') || 8;
    var error = container.attr('data-error') || "Impossible de récupérer les données...";
    var max = parseInt(container.attr('data-max')) || 50;

    // Check if the element already have a row
    if (container.children().length() === 1 && container.children().get(0).matches('.row')) {
        video_row = container.children().get(0);
    } else {
        video_row = $.DOM.create('div').addClass('row');
        container.append(video_row);
    }

    // Add the load more button
    container.append('<div class="load-more"><a href="#" title="Voir plus de vidéos"><i class="far fa-chevron-down"></i></a></div>');

    function fetch(limit, offset, callback) {
        $.api.get(
            url,
            {limit: limit, offset:offset},
            function(response) {

                if (callback !== undefined) {
                    callback()
                }

                if (container.find('.alert-error').exists()) {
                    container.find('.alert-error').remove();
                }

                if (response.success) {
                    $.forEach(response.payload, function(index, video_html) {
                        video_row.append(video_html);
                    });
                    // Hide the load_more button if we loaded all the videos
                    if (max >= 0 && video_row.children().length() >= max) {
                        container.find('.load-more > a').hide();
                    }
                } else {
                    container.prepend('<div class="alert alert-error"><p>{0}</p></div>'.format(error));
                }
            }
        )
    }

    container.find('.load-more > a').on('click', function(e) {
        e.preventDefault();
        Button = e.element;
        Button.html('<i class="far fa-spinner fa-spin"></i>');
        fetch(step, video_row.children().length(), function() {
            Button.html('<i class="far fa-chevron-down"></i>');
        });
    });

    if (video_row.children().length() === 0) {
        fetch(init, 0);
    }
    if (max >= 0 && video_row.children().length() >= max) {
        container.find('.load-more > a').hide();
    }
});


/**
 * FULGUR BACKGROUND LOADING
 */
$.registerElementExtension('background_loading', function() {
    var item = this;
    var image = $.DOM.create('img').attr('src', item.attr('data-url'));
    image.on('load', function(event) {
        event.element.remove();
        item.style('background-image', 'url("{0}")'.format(item.attr('data-url')));
    });
});
