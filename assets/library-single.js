//
// Content Item Object
//
function ContentItem(data) {
    this.content = ko.observable(data.content);
    this.type = (data.type !== undefined) ? ko.observable(data.type) : null;
}

//
// Biblio Meta Item Object
//
function BiblioMetaItem(name, value, disp, placeholder) {
    this.name        = name;
    this.value       = ko.observable(value);
    this.disp        = disp;
    this.placeholder = placeholder;
}

//
// Define a ViewModel for a single document
//
function DocumentViewModel(docUrl) {
    
    var self = this;

    //Meta Data
    self.docId         = '';
    self.availSecTypes = ko.observableArray([]);

    //Content Data
    self.biblioMeta = ko.observableArray([]);
    self.authors    = ko.observableArray([]);
    self.abstract   = ko.observableArray([]);
    self.content    = ko.observableArray([]);
    self.citations  = ko.observableArray([]);

    //Operations
    self.addAuthor = function(index, data, event) {
        if (index !== 'undefined') {
            self.authors.splice(index+1, 0, {name: ''});
        }
        else {
            self.authors.push({ name: '' });    
        } 
    }

    self.addAbstractSection = function(index, data, event) {
        if (index !== 'undefined') {
            self.abstract.splice(index+1, 0, new ContentItem({}));
        }
        else {
            self.abstract.push(new ContentItem({}));
        }
    }

    self.addContentSection = function(index, data, event) {
        if (index !== 'undefined') {
            self.content.splice(index+1, 0, new ContentItem({}));
        }
        else {
            self.content.push(new ContentItem({}));
        }
    }

    self.addCitation = function(index, data, event) {
        if (index !== 'undefined') {
            self.citations.splice(index+1, 0, new ContentItem({}));
        }
        else {
            self.citations.push(new ContentItem({}));
        }
    }

    //Remove an item from an array
    self.removeItem = function(arr, data, event) {
        arr.remove(data);
    }

    //Load initial state from server
    if (docUrl !== undefined) {
        $.getJSON(docUrl, { disp_opts: 'true' }, function(serverData) {
            
            var doc      = serverData.document;
            var dispOpts = serverData.dispOptions;

            //ID
            self.docId = doc.uniqId;

            //Biblio Meta
            $.each(doc.biblioMeta, function (k, v) {
                var dispName = dispOpts.biblioMetaDisp[k].dispName;
                var dispPH   = dispOpts.biblioMetaDisp[k].dispPlaceholder;
                self.biblioMeta.push(new BiblioMetaItem(k, v, dispName, dispPH));
            });

            //Authors
            $.each(doc.authors, function(k, v) {
                self.authors.push(v);
            });

            //Abstract
            $.each(doc.abstract.sections, function(k, v) {
                self.abstract.push(new ContentItem(v));
            });

            //Content
            $.each(doc.content.sections, function(k, v) {
                self.content.push(new ContentItem(v));
            });

            //Citations
            $.each(doc.citations, function(k, v) {
                self.citations.push(new ContentItem(v));
            });

            //Content Types
            $.each(dispOpts.availSecTypes, function(k, v) {
                self.availSecTypes.push({ slug: k, name: v });
            });

        });
    }
}


//
// Apply the bindings
//
$(document).ready(function() {

    var docUrl = $('#workform').data('docurl');

    docViewModel = new DocumentViewModel(docUrl);
    ko.applyBindings(docViewModel); 
})
