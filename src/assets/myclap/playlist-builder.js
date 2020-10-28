$('input#videos').execute(function(input) {
    var videos = JSON.parse(input.get('value')) || [];
    var container = input.parent();
    container.addClass('playlist-builder');
    container.append('<div class="list"><div class="header"><h2>Liste des vidéos</h2><a class="button"><i class="far fa-plus"></i> Ajouter des vidéos</a></div><div class="video-container row horizontal"></div><div class="add"></div></div><div class="search"></div>');

    function update_input() {
        input.set('value', JSON.stringify(videos));
    }

    function add_video(token, position='end') {
        if (videos.indexOf(token) < 0) {
            if (position === 'end') {
                videos.push(token);
            } else {
                videos.unshift(token);
            }
            update_input();
            render_videos([token], position);
        }
    }

    /**
     * Render
     */
    var video_container = container.find('.video-container');
    function render_videos(tokens, position='end') {
        if (tokens.length > 0) {
            $.api.get(
                '/manager/playlists/api/videos',
                {tokens: JSON.stringify(tokens)},
                function(response) {
                    if (response.success) {
                        $.forEach(response.payload, function(index, video_html) {
                            var _video = $.DOM.create('div').html(video_html);
                            if (position === 'end') {
                                video_container.append(_video.html());
                            } else {
                                video_container.prepend(_video.html());
                            }
                        });
                        render_indexes();
                    } else {
                        alert(response.message);
                    }
                }
            )
        }
    }
    function render_indexes() {
        video_container.find('span.video-index').toCollection().each(function(index, span) {
            Token = span.parent('.video').attr('data-token');
            span.text( (videos.indexOf(Token) + 1));
        })
    }

    /**
     * Search
     */
    var search = container.find('div.search');
    search.append('<div class="header"><h2>Ajouter des vidéos</h2><a class="button"><i class="far fa-chevron-left"></i> Revenir à la liste</a></div>');
    search.execute(function(search_container) {
        search_container.append('<div class="max-w-500 ms-auto"><input class="fullwidth" placeholder="Rechercher une vidéo à ajouter"></div><div class="row horizontal"></div>');
        var search_input = search_container.find('input');
        var search_row = search_container.find('.row');

        var input_timeout = null;
        search_input.on('input', function(event) {
            value = event.element.get('value');
            if (input_timeout) { clearTimeout(input_timeout) }
            if (value.length > 0) {
                input_timeout = setTimeout(function() {
                    search_row.html('<div class="fullwidth h-100 layout center"><i class="far fa-spinner fa-spin"></i></div>');
                    $.api.get("/manager/playlists/api/search", {value: value}, function(rep) {
                        if (rep.success) {
                            search_row.html('');
                            $.forEach(rep.payload, function(index, video_html) {
                                var _video = $.DOM.create('div').html(video_html);
                                var token = _video.find('.video').attr('data-token');
                                if (videos.indexOf(token) >= 0) {
                                    _video.find('.buttons-group').remove();
                                }
                                search_row.append(_video.html());
                            });
                        } else {
                            search_row.html('<div class="fullwidth h-100 layout center"><h4><i class="far fa-exclamation-circle"></i> Une erreur est survenue</h4></div>');
                        }
                    })
                }, 500);
            } else {
                search_row.html('');
            }
        });
    });

    /**
     * UI Management
     */
    function list_view() {
        container.attr('data-view', 'list');
    }
    function search_view() {
        container.attr('data-view', 'search');
    }

    /**
     * Listener
     */
    container.on('click', '.list .header > .button', function(event) {
        event.preventDefault();
        search_view();
    });
    container.on('click', '.search .header > .button', function(event) {
        event.preventDefault();
        list_view();
    });
    container.on('click', '.search .header > .button', function(event) {
        event.preventDefault();
        list_view();
    });
    container.on('click', '.search .video .button', function(event) {
        event.preventDefault();
        Token = event.element.parent('.video').attr('data-token');
        if (videos.indexOf(Token) < 0) {
            add_video(Token, event.element.attr('data-position'));
            event.element.parent('.buttons-group').remove();
        } else {
            alert("Cette vidéo est déjà dans la playlist");
        }
    });
    container.on('click', '.list .video .delete', function(event) {
        event.preventDefault();
        Token = event.element.parent('.video').attr('data-token');
        Index = videos.indexOf(Token);
        if (Index >= 0) {
            videos.splice(Index, 1);
        }
        event.element.parent('.video').remove();
        render_indexes();
        update_input();
    });
    container.on('click', '.list .video .up', function(event) {
        event.preventDefault();
        Token = event.element.parent('.video').attr('data-token');
        Index = videos.indexOf(Token);
        Video = event.element.parent('.video');
        if (Index > 0) {
            PrevToken = videos[Index-1];
            videos[Index-1] = Token;
            videos[Index] = PrevToken;
            var PrevVideo = Video._DOMElement.previousSibling;
            Video.remove();
            video_container._DOMElement.insertBefore(Video._DOMElement, PrevVideo);
            render_indexes();
            update_input();
        }
    });
    container.on('click', '.list .video .down', function(event) {
        event.preventDefault();
        Token = event.element.parent('.video').attr('data-token');
        Index = videos.indexOf(Token);
        Video = event.element.parent('.video');
        if (Index < (videos.length-1)) {
            NextToken = videos[Index+1];
            videos[Index+1] = Token;
            videos[Index] = NextToken;
            if (Index === (videos.length-1)) {
                Video.remove();
                video_container.append(Video);
            } else {
                var PrevVideo = Video._DOMElement.nextSibling;
                Video.remove();
                video_container._DOMElement.insertBefore(Video._DOMElement, PrevVideo.nextSibling);
            }
            render_indexes();
            update_input();
        }
    });

    list_view();
    render_videos(videos);
});