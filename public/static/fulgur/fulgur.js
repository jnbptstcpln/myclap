/*!
 * Fulgur v1.0
 */

/**
 * Permet de créer une instance de {Fulgur.Element} ou de {Fulgur.ElementsCollection} à partir d'un sélecteur CSS
 *
 * @function Fulgur
 * @param {String} selector Sélecteur CSS
 * @param {HTMLElement|Fulgur.Element} context Contexte de recherche de l'élément
 * @param {Object} options Options permettant de modifier le comportement de la fonction
 * @returns {Fulgur.Element|Fulgur.ElementsCollection|null}
 */
Fulgur = function(selector, context, options) {

    context = context || document;
    options = options || {};

    if (selector.constructor === String) {
        // Si c'est un sélecteur CSS

        var domElements = null;

        if (context._DOMElement) {
            // Le contexte est une instance de Fulgur.Element
            domElements = context._querySelectorAll(selector);
        } else {
            // On suppose que c'est une instance de DOMElement
            domElements =
                context.querySelectorAll(selector)
        }

        if (domElements.length === 1) {
            if (options.collection) {
                return new Fulgur.ElementsCollection(domElements);
            }
            return new Fulgur.Element(domElements[0]);
        } else if (domElements.length > 1) {
            if (options.one) {
                return new Fulgur.Element(domElements[0]);
            }
            return new Fulgur.ElementsCollection(domElements);
        } else {
            return new Fulgur.Element(null);
        }
    } else if (selector._DOMElement) {
        // Si c'est déjà une instance de Fulgur.Element
        if (options.collection) {
            return new Fulgur.ElementsCollection(selector._DOMElement);
        }
        return selector;
    } else {
        // On suppose que c'est une instance de DOMElement
        if (options.collection) {
            return new Fulgur.ElementsCollection([selector]);
        }
        return new Fulgur.Element(selector);
    }
};
Fulgur.debug = false;

/**
 * Ajoute une extension à Fulgur
 *
 * @function Fulgur.registerExtension
 * @param {String} name Nom de l'extension qui devra être utilisé pour y accéder ({Fulgur.Element.extensionName})
 * @param {Function} extension Fonction qui prend en paramètre un objet contenant des options
 */
Fulgur.registerExtension = function(name, extension) {
    if (Fulgur[name]) {
        throw "The name '"+name+"' can't be use";
    }
    Fulgur[name] = extension;
};

/**
 * Ajoute une extension à {Fulgur.Element}
 *
 * @function Fulgur.registerElementExtension
 * @param {String} name Nom de l'extension qui devra être utilisé pour y accéder ({Fulgur.Element.extensionName})
 * @param {Function} extension Fonction qui prend en paramètre un objet contenant des options
 */
Fulgur.registerElementExtension = function(name, extension) {
    if (Fulgur.Element.prototype[name]) {
        throw "The name '"+name+"' can't be use";
    }
    Fulgur.Element.prototype[name] = function() {
        if (this._DOMElement === null) {
            return;
        }
        extension.apply(this, arguments);
    };
    Fulgur.ElementsCollection.prototype[name] = function() {
        this.each(function(index, element) {
            element[name].apply(element, arguments);
        })
    }
};
/* ELEMENT
 -------------------------------------------------- */

/**
 * Classe basée sur un élement du DOM qui facilite de nombreuses manipulation
 *
 * @class Fulgur.Element
 * @param {HTMLElement} DOMElement
 */
Fulgur.Element = function(DOMElement) {
    /**
     * Référence du {HTMLElement} auquel est lié l'élément
     *
     * @property Fulgur.Element._DOMElement
     * @type {HTMLElement}
     */
    this._DOMElement = DOMElement;

    /**
     * Stocke les différents listeners créés à partir de la méthode {Fulgur.Element.on}
     *
     * @property Fulgur.Element._listeners
     * @type {Object}
     */
    this._listeners = {};

    if (this._DOMElement !== null) {
        if (this._DOMElement.__fulgur) {
            this._listeners = this._DOMElement.__fulgur._listeners;
            delete this._DOMElement.__fulgur;
        }

        this._DOMElement.__fulgur = this;
    }
};


/* ELEMENT | BASICS
-------------------------------------------------- */

/**
 * Permet d'ajouter un enfant à l'élément
 *
 * @method Fulgur.Element.append
 * @param {HTMLElement|Fulgur.Element|String} element L'élément à ajouter
 * @returns {Fulgur.Element} Instance de l'élément
 */
Fulgur.Element.prototype.append = function(element) {

    if (this._DOMElement === null) {
        return this;
    }

    if (typeof element === 'string') {
        this._DOMElement.insertAdjacentHTML('beforeend', element);
    } else if (element._DOMElement) {
        this._DOMElement.appendChild(element._DOMElement);
    } else {
        this._DOMElement.appendChild(element);
    }
    return this;
};
/**
 * Permet d'ajouter un enfant à l'élément en première position
 *
 * @method Fulgur.Element.prepend
 * @param {HTMLElement|Fulgur.Element|String} element L'élément à ajouter
 * @returns {Fulgur.Element} Instance de l'élément
 */
Fulgur.Element.prototype.prepend = function(element) {

    if (this._DOMElement === null) {
        return this;
    }

    if (typeof element === 'string') {
        this._DOMElement.insertAdjacentHTML('afterbegin', element);
    } else if (element._DOMElement) {
        this._DOMElement.insertBefore(element._DOMElement, this._DOMElement.firstChild);
    } else {
        this._DOMElement.insertBefore(element._DOMElement, this._DOMElement.firstChild);
    }
    return this;
};
/**
 * Permet de retirer l'élément du document
 *
 * @method Fulgur.Element.remove
 */
Fulgur.Element.prototype.remove = function() {

    if (this._DOMElement === null) {
        return;
    }

    this._DOMElement.remove();
};
/**
 * Permet de vérifier si l'élement correspond au sélecteur
 *
 * @param {String} selector L'élément à ajouter
 * @method Fulgur.Element.remove
 * @returns {boolean}
 */
Fulgur.Element.prototype.matches = function(selector) {

    if (this._DOMElement === null) {
        return false;
    }

    return this._DOMElement.matches(selector);
};
/**
 * Permet de vérifier si l'élement existe
 *
 * @method Fulgur.Element.exists
 * @returns {boolean}
 */
Fulgur.Element.prototype.exists = function() {
    return  !(this._DOMElement === null);
};

/* ELEMENT | PARENT & CHILDREN
-------------------------------------------------- */

/**
 * Permet de récupérer une instance de {Fulgur.Element} du parent de l'élément
 *
 * @method Fulgur.Element.parent
 * @param {String} selector Sélecteur CSS
 * @returns {Fulgur.Element} Parent de l'élément
 */
Fulgur.Element.prototype.parent = function (selector) {

    if (this._DOMElement === null) {
        return this;
    }

    selector = selector || null;

    if (selector === null) {
        return Fulgur(this._DOMElement.parentElement)
    } else {
        var parent = this._DOMElement.parentElement;
        while (parent !== null) {
            if (parent.matches(selector)) {
                return Fulgur(parent);
            }
            parent = parent.parentElement;
        }
        return new Fulgur.Element(null);
    }
};
/**
 * Permet de récupérer une instance de {Fulgur.ElementsCollection} des enfants directs de l'élément
 *
 * @method Fulgur.Element.children
 * @return {Fulgur.ElementsCollection} Collection des enfants directs de l'élément
 */
Fulgur.Element.prototype.children = function () {

    if (this._DOMElement === null) {
        return new Fulgur.ElementsCollection([]);
    }

    return new Fulgur.ElementsCollection(this._DOMElement.children);
};
/**
 * Recherche le(s) élement(s) correspondant au sélecteur CSS
 *
 * @method Fulgur.Element.find
 * @param {String} selector Sélecteur CSS
 * @returns {Fulgur.Element|Fulgur.ElementsCollection|null}
 */
Fulgur.Element.prototype.find = function (selector) {

    if (this._DOMElement === null) {
        return this;
    }

    return Fulgur(selector, this);
};
/**
 * Permet de récupérer un propriété de {HTMLElement} directement depuis {Fulgur.Element}
 *
 * @method Fulgur.Element.get
 * @param {String} name Nom de la propriété
 * @returns
 */
Fulgur.Element.prototype.get = function (name) {

    if (this._DOMElement === null) {
        return null;
    }

    return this._DOMElement[name];
};
/**
 * Permet de définir une propriété de {HTMLElement} directement depuis {Fulgur.Element}
 *
 * @method Fulgur.Element.get
 * @param {String} name Nom de la propriété
 * @param {*} value Valeur à affecter
 * @returns
 */
Fulgur.Element.prototype.set = function (name, value) {

    if (this._DOMElement === null) {
        return this;
    }

    this._DOMElement[name] = value;
    return this;
};
/**
 * Permet d'utiliser la {HTMLElement.querySelectorAll} directement depuis {Fulgur.Element} avec l'élément comme contexte
 *
 * @method Fulgur.Element.querySelectorAll
 * @param {String} selector Sélecteur CSS
 * @returns {Array} Liste de {HTMLElement}
 */
Fulgur.Element.prototype._querySelectorAll = function (selector) {

    if (this._DOMElement === null) {
        return [];
    }

    return this._DOMElement.querySelectorAll(selector);
};


/* ELEMENT | ATTRIBUTE
-------------------------------------------------- */

/**
 * Affecte la valeur à l'attribut si {value} est fournie, sinon retourne la valeur de l'attribut
 *
 * @method Fulgur.Element.attr
 * @param {String} key Nom de l'attribut
 * @param {String} value Valeur de l'attribut
 * @returns {String|Fulgur.Element}
 */
Fulgur.Element.prototype.attr = function (key, value) {

    if (typeof value === "undefined") {
        if (this._DOMElement === null) {
            return null;
        }
        return this._DOMElement.getAttribute(key);
    } else if (value === null) {
        if (this._DOMElement === null) {
            return this;
        }
        this._DOMElement.removeAttribute(key);
        return this;
    } else {
        if (this._DOMElement === null) {
            return this;
        }
        this._DOMElement.setAttribute(key, value);
        return this;
    }
};


/* ELEMENT | CLASS
-------------------------------------------------- */
/**
 * Ajoute la classe à l'élément
 *
 * @method Fulgur.Element.addClass
 * @param {String} className Nom d'une classe
 * @returns {Fulgur.Element} Instance de l'élémet
 */
Fulgur.Element.prototype.addClass = function(className) {

    if (this._DOMElement === null) {
        return this;
    }

    this._DOMElement.classList.add(className);
    return this;
};
/**
 * Retire la classe à l'élément
 *
 * @method Fulgur.Element.removeClass
 * @param {String} className Nom d'une classe
 * @returns {Fulgur.Element} Instance de l'élémet
 */
Fulgur.Element.prototype.removeClass = function(className) {

    if (this._DOMElement === null) {
        return this;
    }

    this._DOMElement.classList.remove(className);
    return this;
};
/**
 * Indique si l'élément a bien la classe
 *
 * @method Fulgur.Element.hasClass
 * @param {String} className Nom d'une classe
 * @returns {boolean}
 */
Fulgur.Element.prototype.hasClass = function(className) {

    if (this._DOMElement === null) {
        return false;
    }

    return this._DOMElement.classList.contains(className);
};


/* ELEMENT | CONTENT
-------------------------------------------------- */

/**
 * Change le contenu HTML de l'élément si {value} est fournie, sinon retourne le contenu HTML
 *
 * @method Fulgur.Element.html
 * @param {String} value Chaine de caractère contenant de l'HTML
 * @returns {String|Fulgur.Element}
 */
Fulgur.Element.prototype.html = function (value) {
    if (typeof value === "undefined") {
        if (this._DOMElement === null) {
            return null;
        }
        return this._DOMElement.innerHTML;
    } else {
        if (this._DOMElement === null) {
            return this;
        }
        this._DOMElement.innerHTML = value;
        return this;
    }
};
/**
 * Change le contenu texte de l'élément si {value} est fournie, sinon retourne le contenu texte
 *
 * @method Fulgur.Element.text
 * @param {String} value Chaine de caractère contenant du texte
 * @returns {String|Fulgur.Element}
 */
Fulgur.Element.prototype.text = function (value) {
    if (typeof value === "undefined") {
        if (this._DOMElement === null) {
            return null;
        }
        return this._DOMElement.innerText;
    } else {
        if (this._DOMElement === null) {
            return this;
        }
        this._DOMElement.innerText = value;
        return this;
    }
};

/**
 * Execute la fonction donnée en argument en lui passant l'élément en argument
 *
 * @method Fulgur.Element.execute
 * @param {Function} modifier La fonction a executer
 * @returns {Fulgur.Element}
 */
Fulgur.Element.prototype.execute = function (modifier) {
    modifier(this);
    return this;
};

/* ELEMENT | COLLECTION
-------------------------------------------------- */

/**
 * Permet de transformer cet élément simple en une collection d'un seul élément
 *
 * @method Fulgur.Element.toCollection
 * @returns {Fulgur.ElementsCollection} Collection comprenant ce seul élément
 */
Fulgur.Element.prototype.toCollection = function() {

    if (this._DOMElement === null) {
        return new Fulgur.ElementsCollection([]);
    }

    return new Fulgur.ElementsCollection([this._DOMElement]);
};
/* ELEMENTS COLLECTION
 -------------------------------------------------- */
/**
 * Classe permettant de manipuler plusieurs {Fulgur.Element}
 *
 * @class Fulgur.ElementsCollection
 * @param {Array} DOMElements Tableau de {HTMLElement}
 */
Fulgur.ElementsCollection = function (DOMElements) {
    /**
     * @property Fulgur.ElementsCollection.elements
     * @type {Array}
     * @desc Tableau contenant les différentes instances des {Fulgur.Element}
     */
    this.elements = [];
    for (var i = 0; i < DOMElements.length; i++) {
        this.elements.push(new Fulgur.Element(DOMElements[i]));
    }
};
/**
 * Permet de récupérer l'un des élements de la collection
 *
 * @method Fulgur.ElementsCollection.get
 * @param {Number} index Index
 * @returns {Fulgur.Element}
 */
Fulgur.ElementsCollection.prototype.get = function(index) {
    return this.elements[index];
};
/**
 * Renvoie le nombre d'élément que contient la collection
 *
 * @method Fulgur.ElementsCollection.length
 * @returns {Number}
 */
Fulgur.ElementsCollection.prototype.length = function() {
    return this.elements.length;
};
/**
 * Permet d'exécuter une fonction sur tous les éléments de la collection
 *
 * @method Fulgur.ElementsCollection.each
 * @param {Function} callback Fonction prenant en paramètre un index et un élément
 * @returns {Fulgur.ElementsCollection} Instance de la collection
 */
Fulgur.ElementsCollection.prototype.each = function(callback) {
    for (var i = 0; i < this.elements.length; i++) {
        callback(i, this.elements[i]);
    }
    return this;
};
/**
 * Affecte la valeur à l'attribut de chaque élement si {value} est fournie, sinon retourne un tableau avec la valeur de
 * l'attribut pour chaque élément
 *
 * @method Fulgur.ElementsCollection.attr
 * @param {String} key Nom de l'attribut
 * @param {String} value Valeur de l'attribut
 * @returns {Array|Fulgur.ElementsCollection}
 */
Fulgur.ElementsCollection.prototype.attr = function (key, value) {
    if (typeof value === "undefined") {
        var attributes = [];
        this.each(function(index, element) {
            attributes.push(element.attr(key))
        });
        return attributes;
    } else {
        this.each(function(index, element) {
            element.attr(key, value)
        });
        return this;
    }
};
/**
 * Affecte la valeur à l'attribut {value} de chaque élement si {value} est fournie, sinon retourne un objet avec l'attribut {name}
 * en clé et l'attribut {value} en valeur
 *
 * @method Fulgur.ElementsCollection.value
 * @param {String|Object} value Chaine de caractère ou objet avec les attributs name en clé et les valeurs associée à chaque élément
 * @returns {Array|Fulgur.ElementsCollection}
 */
Fulgur.ElementsCollection.prototype.value = function (value) {
    if (typeof value === "undefined") {
        var values = {};
        this.each(function(index, element) {
            if (element.attr('name')) {
                values[element.attr('name')] = element.value()
            }
        });
        return values;
    } else {
        this.each(function(index, element) {
            if (typeof value === 'string') {
                element.value(value)
            } else {
                if (value[element.attr('name')]) {
                    element.value(value[element.attr('name')]);
                }
            }
        });
        return this;
    }
};

/**
 * Execute la fonction donnée en argument sur chaque élément de la collection en leur l'élément en argument
 *
 * @method Fulgur.Element.execute
 * @param {Function} modifier La fonction a executer
 * @returns {Fulgur.Element}
 */
Fulgur.ElementsCollection.prototype.execute = function (modifier) {
    this.each(function(index, element) {
        element.execute(modifier);
    });
    return this;
};

/**
 * Retourne la collection elle même
 *
 * @method Fulgur.ElementsCollection.toCollection
 * @returns {Fulgur.ElementsCollection}
 */
Fulgur.ElementsCollection.prototype.toCollection = function (value) {
    return this;
};
Fulgur.api = {};
/**
 * Permet de créer une requête vers une API de type GET
 *
 * @function Fulgur.api.get
 * @param {String} url URL de la ressource
 * @param {Object} data Données envoyées
 * @param {Function} callback La fonction à executer
 * @param {Object} headers En-têtes à donner à la requête
 */
Fulgur.api.get = function (url, data, callback, headers) {
    headers = headers || {};
    headers['Accept'] = 'application/json';

    var req = new Fulgur.http.GetRequest(url, data, headers);
    req
        .success(function(raw) {
            var response = {};
            try {
                response = JSON.parse(raw.text);
            } catch (e) {
                response = {'success': false, message:'La réponse du serveur est invalide.'}
            }
            callback(response);
        })
        .error(function(raw) {
            var response = {};
            try {
                response = JSON.parse(raw.text);
            } catch (e) {
                response = {'success': false, message:'Une erreur est survenue lors de la requête.'}
            }
            callback(response);
        })
    ;
    req.send();
    return req;
};
/**
 * Permet de créer une requête vers une API de type POST
 *
 * @function Fulgur.api.post
 * @param {String} url URL de la ressource
 * @param {Object} data Données envoyées
 * @param {Function} callback La fonction à executer
 * @param {Object} headers En-têtes à donner à la requête
 */
Fulgur.api.post = function (url, data, callback, headers) {
    headers = headers || {};
    headers['Accept'] = 'application/json';

    var req = new Fulgur.http.PostRequest(url, data, headers);
    req
        .success(function(raw) {
            var response = {};
            try {
                response = JSON.parse(raw.text);
            } catch (e) {
                response = {'success': false, message:'La réponse du serveur est invalide.'}
            }
            callback(response);
        })
        .error(function(raw) {
            var response = {};
            try {
                response = JSON.parse(raw.text);
            } catch (e) {
                response = {'success': false, message:'Une erreur est survenue lors de la requête.'}
            }
            callback(response);
        })
    ;
    req.send();
    return req;
};
/**
 * Permet de créer une requête vers une API de type PUT
 *
 * @function Fulgur.api.put
 * @param {String} url URL de la ressource
 * @param {Object} data Données envoyées
 * @param {Function} callback La fonction à executer
 * @param {Object} headers En-têtes à donner à la requête
 */
Fulgur.api.put = function (url, data, callback, headers) {
    headers = headers || {};
    headers['Accept'] = 'application/json';

    var req = new Fulgur.http.PutRequest(url, data, headers);
    req
        .success(function(raw) {
            var response = {};
            try {
                response = JSON.parse(raw.text);
            } catch (e) {
                response = {'success': false, message:'La réponse du serveur est invalide.'}
            }
            callback(response);
        })
        .error(function(raw) {
            var response = {};
            try {
                response = JSON.parse(raw.text);
            } catch (e) {
                response = {'success': false, message:'Une erreur est survenue lors de la requête.'}
            }
            callback(response);
        })
    ;
    req.send();
    return req;
};
/**
 * Permet de créer une requête vers une API de type DELETE
 *
 * @function Fulgur.api.delete
 * @param {String} url URL de la ressource
 * @param {Function} callback La fonction à executer
 * @param {Object} headers En-têtes à donner à la requête
 */
Fulgur.api.delete = function (url, callback, headers) {
    headers = headers || {};
    headers['Accept'] = 'application/json';

    var req = new Fulgur.http.DeleteRequest(url, data, headers);
    req
        .success(function(raw) {
            var response = {};
            try {
                response = JSON.parse(raw.text);
            } catch (e) {
                response = {'success': false, message:'La réponse du serveur est invalide.'}
            }
            callback(response);
        })
        .error(function(raw) {
            var response = {};
            try {
                response = JSON.parse(raw.text);
            } catch (e) {
                response = {'success': false, message:'Une erreur est survenue lors de la requête.'}
            }
            callback(response);
        })
    ;
    req.send();
    return req;
};
Fulgur.cookie = {};
/**
 * @function Fulgur.cookie.set
 * @param {String} key
 * @param {String} value
 * @param {Object} attributes
 * @returns {string}
 */
Fulgur.cookie.set = function(key, value, attributes) {
    if (typeof document === 'undefined') {
        return;
    }

    function extend () {
        var i = 0;
        var result = {};
        for (; i < arguments.length; i++) {
            var attributes = arguments[ i ];
            for (var key in attributes) {
                result[key] = attributes[key];
            }
        }
        return result;
    }

    attributes = extend({
        path: '/'
    }, attributes);

    if (typeof attributes.expires === 'number') {
        attributes.expires = new Date(new Date() * 1 + attributes.expires * 864e+5);
    }

    // We're using "expires" because "max-age" is not supported by IE
    attributes.expires = attributes.expires ? attributes.expires.toUTCString() : '';

    try {
        var result = JSON.stringify(value);
        if (/^[\{\[]/.test(result)) {
            value = result;
        }
    } catch (e) {}

    value = encodeURIComponent(String(value))
        .replace(/%(23|24|26|2B|3A|3C|3E|3D|2F|3F|40|5B|5D|5E|60|7B|7D|7C)/g, decodeURIComponent);

    key = encodeURIComponent(String(key))
        .replace(/%(23|24|26|2B|5E|60|7C)/g, decodeURIComponent)
        .replace(/[\(\)]/g, escape);

    var stringifiedAttributes = '';
    for (var attributeName in attributes) {
        if (!attributes[attributeName]) {
            continue;
        }
        stringifiedAttributes += '; ' + attributeName;
        if (attributes[attributeName] === true) {
            continue;
        }

        // Considers RFC 6265 section 5.2:
        // ...
        // 3.  If the remaining unparsed-attributes contains a %x3B (";")
        //     character:
        // Consume the characters of the unparsed-attributes up to,
        // not including, the first %x3B (";") character.
        // ...
        stringifiedAttributes += '=' + attributes[attributeName].split(';')[0];
    }

    return (document.cookie = key + '=' + value + stringifiedAttributes);
};
/**
 * @function Fulgur.cookie.get
 * @param {String} key
 * @param {Boolean} json
 * @returns {String|Object}
 */
Fulgur.cookie.get = function(key, json) {
    if (typeof document === 'undefined') {
        return;
    }

    function decode (s) {
        return s.replace(/(%[0-9A-Z]{2})+/g, decodeURIComponent);
    }

    var jar = {};
    // To prevent the for loop in the first place assign an empty array
    // in case there are no cookies at all.
    var cookies = document.cookie ? document.cookie.split('; ') : [];
    var i = 0;

    for (; i < cookies.length; i++) {
        var parts = cookies[i].split('=');
        var cookie = parts.slice(1).join('=');

        if (!json && cookie.charAt(0) === '"') {
            cookie = cookie.slice(1, -1);
        }

        try {
            var name = decode(parts[0]);
            cookie = decode(cookie);

            if (json) {
                try {
                    cookie = JSON.parse(cookie);
                } catch (e) {}
            }

            jar[name] = cookie;

            if (key === name) {
                break;
            }
        } catch (e) {}
    }

    return key ? jar[key] : jar;
};
/**
 * @function Fulgur.cookie.remove
 * @param {String} key
 * @param {Object} attributes
 */
Fulgur.cookie.remove = function(key, attributes) {
    function extend () {
        var i = 0;
        var result = {};
        for (; i < arguments.length; i++) {
            var attributes = arguments[ i ];
            for (var key in attributes) {
                result[key] = attributes[key];
            }
        }
        return result;
    }

    Fulgur.cookie.set(key, '', extend(attributes, {
        expires: -1
    }));
};
Fulgur.DOM = {};
/**
 * Permet de créer un simple élement vide
 *
 * @function Fulgur.DOM.create
 * @param {String} tagName Nom de l'étiquette de l'élement à créer
 * @returns {Fulgur.Element} Élément créé
 */
Fulgur.DOM.create = function(tagName) {
    var e = document.createElement(tagName);
    return Fulgur(e);
};
/**
 * Affecte la valeur à la propriété CSS si {value} est fournie, sinon retourne la valeur de la propriété CSS
 *
 * @method Fulgur.Element.style
 * @param {String} key Nom de la propriété
 * @param {String} value Valeur
 * @returns {String|Fulgur.Element}
 */
Fulgur.Element.prototype.style = function(key, value, animate) {
    if (typeof value == "undefined") {
        if (this._DOMElement === null) {
            return null;
        }
        return this._DOMElement.style[key];
    } else {
        if (this._DOMElement === null) {
            return this;
        }
        this._DOMElement.style[key] = value;
        return this;
    }
};
/**
 * Permet d'obtenir les valeurs calculées du style de l'élément
 *
 * @method Fulgur.Element.getComputedStyle
 * @param {String} pseudoElt Nom optionnel du pseudo élément à récupérer (ex: :before)
 * @returns {CSSStyleDeclaration}
 */
Fulgur.Element.prototype.getComputedStyle = function(pseudoElt) {
    return window.getComputedStyle(this._DOMElement, pseudoElt || null);
};
/**
 * Permet de cacher un élement
 *
 * @method Fulgur.Element.hide
 */
Fulgur.Element.prototype.hide = function() {
    return this.style('display', 'none')
};
/**
 * Permet d'afficher un élement
 *
 * @method Fulgur.Element.show
 */
Fulgur.Element.prototype.show = function() {
    return this.style('display', '')
};
/**
 * Définit la largueur de l'élément si value est fournie, sinon retourne la valeur de la largeur
 *
 * @method Fulgur.Element.width
 * @param {String|Number} value Valeur: soit un nombre, soit une chaine de caractère (ex: 50vw)
 * @returns {Number|Fulgur.Element}
 */
Fulgur.Element.prototype.width = function(value) {
    if (typeof value === "undefined") {
        return Math.trunc(Number(this.getComputedStyle()['width'].replace('px' , '')));
    } else {
        if (typeof value === "string") {
            this.style('width', value)
        } else {
            this.style('width', value+'px')
        }
        return this;
    }
};
/**
 * Définit la hauteur de l'élément si value est fournie, sinon retourne la valeur de la hauteur
 *
 * @method Fulgur.Element.height
 * @param {String|Number} value Valeur: soit un nombre, soit une chaine de caractère (ex: 50vh)
 * @returns {Number|Fulgur.Element}
 */
Fulgur.Element.prototype.height = function(value) {
    if (typeof value === "undefined") {
        return Math.trunc(Number(this.getComputedStyle()['height'].replace('px' , '')));
    } else {
        if (typeof value === "string") {
            this.style('height', value)
        } else {
            this.style('height', value+'px')
        }
        return this;
    }
};

/**
 * Affecte la valeur à la propriété CSS de chaque élement si {value} est fournie, sinon retourne un tableau avec la
 * valeur de de la propriété CSS pour chaque élément
 *
 * @method Fulgur.ElementsCollection.style
 * @param {String} key Nom de la propriété
 * @param {String} value Valeur
 * @returns {Array|Fulgur.ElementsCollection}
 */
Fulgur.ElementsCollection.prototype.style = function(key, value) {
    if (typeof value == "undefined") {
        var attributes = [];
        this.each(function(index, element) {
            attributes.push(element.style(key))
        });
        return attributes;
    } else {
        this.each(function(index, element) {
            element.style(key, value)
        });
        return this;
    }
};
/**
 * Permet de cacher chaque élement
 *
 * @method Fulgur.ElementsCollection.hide
 */
Fulgur.ElementsCollection.prototype.hide = function() {
    this.style('display', 'none')
};
/**
 * Permet d'afficher chaque élement
 *
 * @method Fulgur.ElementsCollection.show
 */
Fulgur.ElementsCollection.prototype.show = function() {
    this.style('display', '')
};
/* ELEMENT | EVENT MANAGEMENT
 -------------------------------------------------- */

/**
 * Permet de lier une fonction à l'évènement
 *
 * @method Fulgur.Element.on
 * @param {String} identifier Identifiant de l'évènement
 * @param {String} selector Selecteur de l'élement cible
 * @param {Function} callback Fonction prenant en argument l'évènement
 * @returns {Fulgur.Element} Instance de l'élément
 */
Fulgur.Element.prototype.on = function(identifier, selector, callback) {

    if (this._DOMElement === null) {
        return this;
    }

    if (typeof callback === 'undefined') {
        callback = selector;
        selector = null;
    }
    var type = identifier.split('.', 1)[0];
    var _callback = function(event) {

        event.data = event.detail || {};

        if (selector !== null) {
            event.element = null;

            var target = $(event.target);
            if (target.exists()) {
                if (target.matches(selector)) {
                    event.element = target;
                } else {
                    var parent = target.parent(selector);
                    if (parent.exists()) {
                        event.element = parent;
                    }
                }
            }

            if (event.element !== null) {
                callback(event);
            }

        } else {
            event.element = this;
            callback(event);
        }

    }.bind(this);

    this._DOMElement.addEventListener(type, _callback);

    if (!this._listeners[identifier]) {
        this._listeners[identifier] = [];
    }
    this._listeners[identifier].push({type: type, selector: selector, callback: _callback});
    return this;
};
/**
 * Permet de lier une fonction à l'évènement
 *
 * @method Fulgur.ElementsCollection.on
 * @param {String} identifier Identifiant de l'élément
 * @param {String} selector Selecteur de l'élement cible
 * @param {Function} callback Fonction prenant en argument l'évènement
 * @param {Object} data Données transmise à travers {event.data}
 * @returns {Fulgur.ElementsCollection} Instance de la collection
 */
Fulgur.ElementsCollection.prototype.on = function(identifier, selector, callback) {
    this.each(function(index, element) {
        element.on(identifier, selector, callback);
    });
    return this;
};
/**
 * Permet de délier toutes les fonctions liées à l'évènement
 *
 * @method Fulgur.Element.off
 * @param {String} identifier Identifiant de l'évènement
 * @param {String} selector Sélecteur de l'élément cible de l'évènement
 * @returns {Fulgur.Element} Instance de l'évènement
 */
Fulgur.Element.prototype.off = function(identifier, selector) {

    if (this._DOMElement === null) {
        return this;
    }

    selector = selector || null;

    if (this._listeners[identifier]) {
        for (var i = 0; i<this._listeners[identifier].length; i++) {
            console.log(this._listeners[identifier][i]);
            if (this._listeners[identifier][i].selector === selector) {
                this._DOMElement.removeEventListener(this._listeners[identifier][i].type, this._listeners[identifier][i].callback);
                this._listeners[identifier].splice(i, 1);
            }
        }
        if (this._listeners[identifier].length === 0) {
            delete this._listeners[identifier];
        }
    }
    return this;
};
/**
 * Permet de délier toutes les fonctions liées à l'évènement pour tous les élément de la collection
 *
 * @method Fulgur.ElementsCollection.off
 * @param {String} identifier Identifiant de l'élément
 * @returns {Fulgur.ElementsCollection} Instance de la collection
 */
Fulgur.ElementsCollection.prototype.off = function(identifier) {
    this.each(function(index, element) {
        element.off(identifier);
    });
    return this;
};
/**
 * Permet de déclencher un évènement sur l'élément
 *
 * @method Fulgur.Element.trigger
 * @param {String} identifier Identifiant de l'évènement
 * @param {object} data Des données à passer avec l'évènement
 * @returns {Fulgur.Element} Instance de l'élément
 */
Fulgur.Element.prototype.trigger = function(identifier, data) {

    if (this._DOMElement === null) {
        return this;
    }

    var type = identifier.split('.', 1)[0];

    var event = new CustomEvent(type, {
        'bubbles': true,
        'cancelable': true,
        'detail': data
    });
    this._DOMElement.dispatchEvent(event);

    return this;

};
/**
 * Permet de déclencher un évènement sur tous les éléments de la collection
 *
 * @method Fulgur.ElementsCollection.trigger
 * @param {String} identifier Identifiant de l'élément
 * @returns {Fulgur.ElementsCollection} Instance de la collection
 */
Fulgur.ElementsCollection.prototype.trigger = function(identifier) {
    this.each(function(index, element) {
        element.trigger(identifier);
    });
    return this;
};
Fulgur.http = {};
Fulgur.http.METHOD_POST = 'POST';
Fulgur.http.METHOD_GET = 'GET';
Fulgur.http.METHOD_PUT = 'PUT';
Fulgur.http.METHOD_DELETE = 'DELETE';
Fulgur.http.x = function () {
    if (typeof XMLHttpRequest !== 'undefined') {
        return new XMLHttpRequest();
    }
    var versions = [
        "MSXML2.XmlHttp.6.0",
        "MSXML2.XmlHttp.5.0",
        "MSXML2.XmlHttp.4.0",
        "MSXML2.XmlHttp.3.0",
        "MSXML2.XmlHttp.2.0",
        "Microsoft.XmlHttp"
    ];

    var xhr;
    for (var i = 0; i < versions.length; i++) {
        try {
            xhr = new ActiveXObject(versions[i]);
            break;
        } catch (e) {
        }
    }
    return xhr;
};
/**
 * Permet d'éxecuter un appel HTTP de "bas niveau"
 *
 * @function Fulgur.http.send
 * @param {String} url URL de la ressource
 * @param {Function} callback Fonction prenant en paramètres les données récupérées
 * @param {String} method Méthode HTTP utilisée
 * @param {Object} data Données envoyées
 * @param {Boolean} async Permet de définir la nature asynchrone de la requête
 */
Fulgur.http.send = function (url, callback, method, data, async) {
    if (async === undefined) {
        async = true;
    }
    var x = this.x();
    x.open(method, url, async);
    x.onreadystatechange = function ()
    {
        if (x.readyState === 4) {
            if (Fulgur.debug) {
                console.debug(x);
            }
            callback(x.responseText)
        }
    };
    if (method === Fulgur.http.METHOD_POST) {
        x.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');
    }
    x.send(data)
};
/**
 * Permet de créer une requête HTTP de type GET
 *
 * @function Fulgur.http.get
 * @param {String} url URL de la ressource
 * @param {Object} data Données envoyées
 * @param {Object} headers En-têtes à donner à la requête
 */
Fulgur.http.get = function (url, data, headers) {
    return new Fulgur.http.GetRequest(url, data, headers);
};
Fulgur.GET = Fulgur.http.get;
/**
 * Permet de créer une requête HTTP de type POST
 *
 * @function Fulgur.http.post
 * @param {String} url URL de la ressource
 * @param {Object} data Données envoyées
 * @param {Object} headers En-têtes à donner à la requête
 */
Fulgur.http.post = function (url, data, headers) {
    return new Fulgur.http.PostRequest(url, data, headers);
};
Fulgur.POST = Fulgur.http.post;
/**
 * Permet de créer une requête HTTP de type PUT
 *
 * @function Fulgur.http.put
 * @param {String} url URL de la ressource
 * @param {Object} data Données envoyées
 * @param {Object} headers En-têtes à donner à la requête
 */
Fulgur.http.put = function (url, data, headers) {
    return new Fulgur.http.PutRequest(url, data, headers);
};
Fulgur.PUT = Fulgur.http.put;
/**
 * Permet de créer une requête HTTP de type DELETE
 *
 * @function Fulgur.http.delete
 * @param {String} url URL de la ressource
 * @param {Object} headers En-têtes à donner à la requête
 */
Fulgur.http.delete = function (url, headers) {
    return new Fulgur.http.DeleteRequest(url, headers);
};
Fulgur.DELETE = Fulgur.http.put;
/**
 * Permet de créer une requête HTTP de type POST pour envoyer des fichiers
 *
 * @function Fulgur.http.upload
 * @param {String} url URL de la ressource
 * @param {Object} files Dictionnaire des fichiers à envoyer
 * @param {Object} headers En-têtes à donner à la requête
 */
Fulgur.http.upload = function (url, files, headers) {
    return new Fulgur.http.FileUploadRequest(url, files, headers);
};
Fulgur.upload = Fulgur.http.upload;

/**
 * Classe abstraite permettant de préparer et d'effectuer une requête HTTP
 *
 * @param type
 * @param url
 * @param data
 * @param headers
 * @constructor
 */
Fulgur.http.Request = function(type, url, data, headers) {
    // Define properties
    this.type = type;
    this.url = url || "";
    this.data = data || {};
    this.headers = headers || {};

    this.sent = false;
    this.aborted = false;

    // Define callbacks
    this.onload = function() {}; // execute when the response is received, whether it is successful or not
    this.onsuccess = function() {}; // execute when the response is successful (based on http code : 100 <= x <= 300)
    this.onerror = function() {}; // execute when the response is not successful (based on http code)
    this.ondownloadprogress = function() {}; // execute during the progress of downloading
    this.onuploadprogress = function() {}; // execute during the progress of uploading

    // Create and open the session
    var x = Fulgur.http.x();
    x.open(this.type, this.url, true);

    // Setup the headers
    for (var name in headers) {
        x.setRequestHeader(name, headers[name]);
    }

    // Registrer success and error callbacks
    x.onreadystatechange = function() {
        if (x.readyState === 4 && !this.aborted) {
            if (Fulgur.debug) {
                console.debug(x);
            }
            var response = {
                status: {
                    code: x.status,
                    text: x.statusText
                },
                text: x.responseText,
                type: x.responseType
            };
            this.onload();
            if (100 <= x.status && x.status < 400) {
                this.onsuccess(response);
            } else {
                this.onerror(response);
            }
        }
    }.bind(this);
    // Registrer download progress callback
    x.onprogress = function(event) {
        if (Fulgur.debug) {
            console.debug(event);
        }
        var progress = {
            computable: event.lengthComputable,
            percentage: null
        };
        if (event.lengthComputable) {
            progress.percentage = (event.loaded / event.total) * 100;
        }
        this.ondownloadprogress(progress);
    }.bind(this);
    // Registrer upload progress callback
    x.upload.onprogress = function(event) {
        if (Fulgur.debug) {
            console.debug(event);
        }
        var progress = {
            computable: event.lengthComputable,
            percentage: null
        };
        if (event.lengthComputable) {
            progress.percentage = (event.loaded / event.total) * 100;
        }
        this.onuploadprogress(progress);
    }.bind(this);

    // Setup method to registrer callback
    this.load = function(callback) {
        this.onload = callback;
        return this;
    };
    this.success = function(callback) {
        this.onsuccess = callback;
        return this;
    };
    this.error = function(callback) {
        this.onerror = callback;
        return this;
    };
    this.timeout = function(callback, time) {
        x.timeout = time;
        x.ontimeout = callback;
        return this;
    };
    this.uploadprogress = function(callback) {
        this.onuploadprogress = callback;
        return this;
    };
    this.downloadprogress = function(callback) {
        this.ondownloadprogress = callback;
        return this;
    };

    // Setup method to registrer a handler
    this.delegate = function(handler) {
        this.load(handler.onload || function() {});
        this.success(handler.onsuccess || function() {});
        this.error(handler.onerror || function() {});
        this.timeout(handler.ontimeout || function() {});
        this.uploadprogress(handler.onuploadprogress || function() {});
        this.downloadprogress(handler.ondownloadprogress || function() {});
        return this;
    };

    // Setup method that actually send the request
    this.send = function() {
        var formData = null;
        if (this.data) {
            formData = new FormData();
            for(var key in this.data) {
                formData.append(key, this.data[key]);
            }
        }
        this.sent = true;
        x.send(formData);

    };
    this.abort = function() {
        this.aborted = true;
        x.abort();

    }
};

/**
 * Classe permettant de préparer et d'effectuer une requête HTTP de type GET
 *
 * @param url
 * @param data
 * @param headers
 * @constructor
 */
Fulgur.http.GetRequest = function(url, data, headers) {
    // Building the URL
    var query = [];
    for (var key in data) {
        query.push(encodeURIComponent(key) + '=' + encodeURIComponent(data[key]));
    }
    if (url.indexOf('?') >= 0) {
        url += (query.length ? '&' + query.join('&') : '');
    } else {
        url += (query.length ? '?' + query.join('&') : '');
    }

    // Instanciate parent
    Fulgur.http.Request.call(this, Fulgur.http.METHOD_GET, url, null, headers);
};

/**
 * Classe permettant de préparer et d'effectuer une requête HTTP de type POST
 *
 * @param url
 * @param data
 * @param headers
 * @constructor
 */
Fulgur.http.PostRequest =  function(url, data, headers) {
    // Instanciate parent
    Fulgur.http.Request.call(this, Fulgur.http.METHOD_POST, url, data, headers);
};

/**
 * Classe permettant de préparer et d'effectuer une requête HTTP de type PUT
 *
 * @param url
 * @param data
 * @param headers
 * @constructor
 */
Fulgur.http.PutRequest = function(url, data, headers) {
    // Instanciate parent
    Fulgur.http.Request.call(this, Fulgur.http.METHOD_PUT, url, data, headers);
};

/**
 * Classe permettant de préparer et d'effectuer une requête HTTP de type DELETE
 *
 * @param url
 * @param headers
 * @constructor
 */
Fulgur.http.DeleteRequest = function(url, headers) {
    // Instanciate parent
    Fulgur.http.Request.call(this, Fulgur.http.METHOD_DELETE, url, null, headers);
};

/**
 * Classe donnant la structure du Handler HTTP
 *
 * @constructor
 */
Fulgur.http.Handler = function(parent) {

    parent = parent || {};

    // Define callbacks
    this.onload = parent.onload || function() {}; // execute when the response is received, whether it is successful or not
    this.onsuccess = parent.onsuccess || function() {}; // execute when the response is successful (based on http code : 100 <= x <= 300)
    this.onerror = parent.onerror || function() {}; // execute when the response is not successful (based on http code)
    this.ondownloadprogress  = parent.ondownloadprogress || function() {}; // execute during the progress of downloading
    this.onuploadprogress = parent.onuploadprogress || function() {}; // execute during the progress of uploading

    // Setup method to registrer callback
    this.load = function(callback) {
        this.onload = callback;
        return this;
    };
    this.success = function(callback) {
        this.onsuccess = callback;
        return this;
    };
    this.error = function(callback) {
        this.onerror = callback;
        return this;
    };
    this.timeout = function(callback, time) {
        x.timeout = time;
        x.ontimeout = callback;
        return this;
    };
    this.uploadprogress = function(callback) {
        this.onuploadprogress = callback;
        return this;
    };
    this.downloadprogress = function(callback) {
        this.ondownloadprogress = callback;
        return this;
    };

    this.createLinkedHandler = function() {
        var parent_handler = this;
        var handler = new Fulgur.http.Handler(parent_handler);
        // Setup method to registrer callback
        handler.load = function(callback) {
            this.onload = function (a) {
                callback(parent_handler.onload(a));
            };
            return this;
        };
        handler.success = function(callback) {
            this.onsuccess = function (a) {
                callback(parent_handler.onsuccess(a));
            };
            return this;
        };
        handler.error = function(callback) {
            this.onerror = function (a) {
                callback(parent_handler.onerror(a));
            };
            return this;
        };
        handler.timeout = function(callback, time) {
            x.timeout = time;
            x.ontimeout = function (a) {
                callback(parent_handler.ontimeout(a));
            };
            return this;
        };
        handler.uploadprogress = function(callback) {
            this.onuploadprogress = function (a) {
                callback(parent_handler.onuploadprogress(a));
            };
            return this;
        };
        handler.downloadprogress = function(callback) {
            this.ondownloadprogress = function (a) {
                callback(parent_handler.ondownloadprogress(a));
            };
            return this;
        };

        return handler;
    }
};
Fulgur.selection = {};
/**
 * Permet de récupérer la selection actuelle
 *
 * @function Fulgur.selection.save
 * @returns {Range|null} {Range} correspondant à la sélection actuelle sous la forme d'une instance de {Range}
 */
Fulgur.selection.save = function () {
    if (window.getSelection) {
        var sel = window.getSelection();
        if (sel.getRangeAt && sel.rangeCount) {
            return sel.getRangeAt(0);
        }
    } else if (document.selection && document.selection.createRange) {
        return document.selection.createRange();
    }
    return null;
};
/**
 * Permet de définir la sélection à partir d'une instance de {Range}
 *
 * @function Fulgur.selection.restore
 * @param {Range} range {Range} représentant une sélection
 */
Fulgur.selection.restore = function(range) {
    if (range) {
        if (window.getSelection) {
            var sel = window.getSelection();
            sel.removeAllRanges();
            sel.addRange(range);
        } else if (document.selection && range.select) {
            range.select();
        }
    }
};
/**
 * Permet de tout dé-sélectionner
 *
 * @function Fulgur.selection.clear
 */
Fulgur.selection.clear =function() {
    var sel = window.getSelection();
    sel.removeAllRanges();
};
/**
 * Définit la sélection sur l'élément
 *
 * @function Fulgur.selection.select
 * @param {Fulgur.Element|HTMLElement} el
 */
Fulgur.selection.select = function(el) {
    if (el._DOMElement) {
        el = el._DOMElement;
    }
    var range = document.createRange();
    range.selectNodeContents(el);
    var sel = window.getSelection();
    sel.removeAllRanges();
    sel.addRange(range);
};
/**
 * Récupèrele contenu HTML de la sélection actuelle
 *
 * @function Fulgur.selection.html
 * @returns {string} Chaine de caractère contenant de l'HTML
 */
Fulgur.selection.html = function() {
    var html = "";
    if (typeof window.getSelection != "undefined") {
        var sel = window.getSelection();
        if (sel.rangeCount) {
            var container = document.createElement("div");
            for (var i = 0, len = sel.rangeCount; i < len; ++i) {
                container.appendChild(sel.getRangeAt(i).cloneContents());
            }
            html = container.innerHTML;
        }
    } else if (typeof document.selection != "undefined") {
        if (document.selection.type == "Text") {
            html = document.selection.createRange().htmlText;
        }
    }
    return html;
};
/**
 * Permet d'exécuter une fonction sur tous les éléments du tableau
 *
 * @function Fulgur.forEach
 * @param {Array} array Tableau
 * @param {Function} callback Fonction prenant en paramètre un index et un élément
 */
Fulgur.forEach = function(array, callback) {
    for (var i in array) {
        callback(i, array[i]);
    }
};
/**
 * Permet de vérifier si un élement existe dans le contexte ({document} par défaut)
 *
 * @param {String} selecteur Sélecteur CSS
 * @param {Fulgur.Element|HTMLElement} context Contexte dans lequel est effectuée la recherche
 * @returns {boolean}
 */
Fulgur.exists = function(selecteur, context) {
    context = context || document;

    if (context._DOMElement) {
        // Le contexte est une instance de Fulgur.Element
        return context._querySelectorAll(selector).length > 0;
    } else {
        // On suppose que c'est une instance de DOMElement
        return context.querySelectorAll(selector).length > 0;
    }
};
/**
 * Permet de récupérer la valeur d'un paramètre passé dans un url
 *
 * @function Fulgur.getQueryParameter
 * @param {String} name Nom du paramètre
 * @param {String} url URL dans laquelle il faut récupérer le paramètre
 * @returns {*}
 */
Fulgur.getQueryParameter = function(name, url) {
    if (!url) url = window.location.href;
    name = name.replace(/[\[\]]/g, '\\$&');
    var regex = new RegExp('[?&]' + name + '(=([^&#]*)|&|#|$)'),
        results = regex.exec(url);
    if (!results) return null;
    if (!results[2]) return '';
    return decodeURIComponent(results[2].replace(/\+/g, ' '));
};
/**
 * Permet de formater une chaine de caractère
 *
 * @function Fulgur.getQueryParameter
 * @param {String} format Template et arguments
 * @returns {String}
 */
Fulgur.format = function(format) {
    var args = Array.prototype.slice.call(arguments, 1);
    if (args.length === 1 && typeof args[0] === 'object') {
        var replacements = args[0];
        return format.replace(/{([\w]+)}/g, function(match, key) {
            return typeof replacements[key] !== 'undefined'
                ? replacements[key]
                : match
                ;
        });
    } else {
        return format.replace(/{(\d+)}/g, function(match, number) {
            return typeof args[number] !== 'undefined'
                ? args[number]
                : match
                ;
        });
    }

};