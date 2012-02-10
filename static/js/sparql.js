rdf_type = 'http://www.w3.org/1999/02/22-rdf-syntax-ns#type';
RDF = 'http://www.w3.org/1999/02/22-rdf-syntax-ns#';
CHSI = 'http://logd.tw.rpi.edu/source/data-gov/dataset/2159/vocab/enhancement/1/';
DATACUBE = 'http://logd.tw.rpi.edu/source/data-gov/datacube/';
RDFS = 'http://www.w3.org/2000/01/rdf-schema#';
RO = 'http://www.obofoundry.org/ro/ro.owl#';
rdfs_label = RDFS+'label';

var shortcuts = {
    "label":RDFS+"label",
    "type":RDF+"type",
    "partOf":RO+"part_of",
    "hasPart":RO+"has_part"
};

graph = {}
graph.getResource = function(uri) {
    var result = graph[uri];
    if (result == null) {
        result = {uri: uri};
        graph[uri] = result;
    }
    return result;
};
graph.byClass = function(c) {
    return d3.keys(graph).filter(function(k) {
        if (k == "getResource"|| k=="byClass") return false;
        var d = graph[k];
        if (d[rdf_type] == null) return false;
        return d[rdf_type].some(function(element) {
            return element.uri == c;
        });
    }).map(function(k) {
        return graph[k];
    });
};
graph.add = function(data) {
    var result = {}
    d3.keys(data).forEach(function(uri) {
        var subject = graph.getResource(uri);
        result[uri] = subject;
        d3.keys(data[uri]).forEach(function(predicate) {
            subject[predicate] = data[uri][predicate].map(function(obj) {
                if (obj.type == "literal") {
                    return obj.value;
                } else {
                    return graph.getResource(obj.value);
                }
            })
        })
        d3.keys(shortcuts).forEach(function(shortcut) {
            subject[shortcut] = subject[shortcuts[shortcut]];
        });
    });
    return result;
};

String.prototype.format = function() {
    var formatted = this;
    for (var i = 0; i < arguments.length; i++) {
        var regexp = new RegExp('\\{'+i+'\\}', 'gi');
        formatted = formatted.replace(regexp, arguments[i]);
    }
    return formatted;
};

function sparqlConstruct(query, endpoint, callback) {
    var encodedQuery = encodeURIComponent(query);
    var url = "http://logd.tw.rpi.edu/sparql.php?query="+encodedQuery+"&output=rdfjson&service-uri="+endpoint;
    console.log(url);
    d3.json(url, callback);
}

function sparqlSelect(query, endpoint, callback) {
    var encodedQuery = encodeURIComponent(query);
    var url = "http://logd.tw.rpi.edu/sparql.php?query="+encodedQuery+"&output=sparqljson&service-uri="+endpoint;
    d3.json(url, callback);
}

