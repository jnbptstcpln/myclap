String.prototype.sansAccent = function(){
    var accent = [
        /[\300-\306]/g, /[\340-\346]/g, // A, a
        /[\310-\313]/g, /[\350-\353]/g, // E, e
        /[\314-\317]/g, /[\354-\357]/g, // I, i
        /[\322-\330]/g, /[\362-\370]/g, // O, o
        /[\331-\334]/g, /[\371-\374]/g, // U, u
        /[\321]/g, /[\361]/g, // N, n
        /[\307]/g, /[\347]/g, // C, c
    ];
    var noaccent = ['A','a','E','e','I','i','O','o','U','u','N','n','C','c'];

    var str = this;
    for(var i = 0; i < accent.length; i++){
        str = str.replace(accent[i], noaccent[i]);
    }

    return str;
};
String.prototype.hasSubString = function (string2, case_insensitive=true, accent_insensitive=true) {
    var string1 = this;
    if (case_insensitive) {
        string1 = string1.toLowerCase();
        string2 = string2.toLowerCase();
    }
    if (accent_insensitive) {
        string1 = string1.sansAccent();
        string2 = string2.sansAccent();
    }
    return string1.indexOf(string2) >= 0;
};
String.prototype.format = function() {
    return Fulgur.format(this, ...arguments);
};