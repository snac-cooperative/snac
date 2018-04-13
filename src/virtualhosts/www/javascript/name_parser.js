/**
 * Name Parser
 *
 * Attempts to parse names.
 *
 * @author Robbie Hott, Joseph Glass
 * @license http://opensource.org/licenses/BSD-3-Clause BSD 3-Clause
 * @copyright 2017 the Rector and Visitors of the University of Virginia
 */

/**
 * Person Name Parser
 *
 * Attempts to parse names that are in RDA format into their SNAC-defined
 * components.
 */

var NameParser = function(name) {
    this.name =  name || '';
    this.parts = this.splitName(name);
    this.nameAdditions = [];

};

NameParser.prototype.splitName = function(name) {
    var result = name.split(/,|(\(.*?\))/).map( function(part) {
            if (part) {
                return part.trim();
            }
    });
    return result.filter(function(e) {return e;});
};


NameParser.prototype.guessPerson = function() {

    var guesses = [];
    guesses.push(this.parsePerson());

    // make a guess, push guess object

    if (!this.surname && this.forename.match(/ /)) {
        var parts = this.forename.split(/ (.+)/);
        this.surname = parts[0];
        this.forename = parts[1];
    }
    guesses.push(this.parsePerson());


    // if date, insert comma before first digit

};


NameParser.prototype.displayPerson = function() {
    var display = { "Surname" : this.surname,
                    "Forename" : this.forename,
                    "NameExpansion" : this.nameExpansion,
                    "Numeration" : this.numeration,
                    "NameAdditions" : this.nameAdditions,
                    "Date" : this.date };

    Object.keys(display).forEach(function(key) {
        if ( !display[key] || display[key].length == 0) {
            delete(display[key]);
        }
    });

    return display;
};

NameParser.prototype.parsePerson = function() {
    this.parseDate();
    this.parseNumeration();
    // console.log(this);
    var length = this.parts.length;
    if (length == 1) {        // If there is only one name part, it defaults to forename
        this.forename = this.parts[0];
        return this.displayPerson();      // what if there just aren't any commas?
    }

    for (var i = 0; i < length; i++) {
        var part = this.parts[i];
        var lowered = part.toLowerCase();
        // console.log("Part: ", part)
        if (i === 0) {
            this.surname = part;    // First part is assumed to be surname
            // console.log("surname: ", part)
            continue;
        }

        if (i === 1) {
            if (part.startsWith('(')) {
                // console.log("nameadd1: ", part)
                this.nameAdditions.push(part);
            }
            else if (lowered.includes("emperor") ||
                    lowered.includes("empress") ||
                    lowered.includes("king") ||
                    lowered.includes("queen") ||
                    lowered.includes("prince") ||
                    lowered.includes("chief")) {
                this.nameAdditions.push(part);
            }
            else {
                // console.log("forename: ", part)
                this.forename = part;
            }
            // If the previous part was a forename and this piece had parens, then
            // it should be a name expansion
                // TODO: Question: Are expansions always preceded by forenames?
                // Improve this? check if parts of first letter on name expansion matches forename
        } else if (this.parts[i - 1] === this.forename && part.startsWith('(')) {
            this.nameExpansion = part;
            // console.log("expans: ", part)
        } else {
            this.nameAdditions.push(part); // Anything not known is officially a name addition
            // console.log("nameadd2: ", part)

        }
    }
    // if there's only one name, it should default to forename, not surname
    if (this.forename === undefined && this.surname) {
        this.forename = this.surname;
        this.surname = undefined;
    }
    // console.log("End result", this)
    return this.displayPerson();

};


    // Since you can't have a surname without a forename, if this piece was not set
    // to be a forename and the previous part was a surname, then update the previous
    // to be a forename instead
        // at end, if the thing after surname is not forname, then forename = surname, surname = undefined


NameParser.prototype.parseDate = function() {
    for (var i=0; i < this.parts.length; i++) {
        // TODO: fails for Carleton (Family : Carleton, James, 1757-1827 )
        // grab from first digit to last
        if (this.parts[i].match(/\d+|\d+\s*-|-\s*\d+|\d+\s*-\s*\d+/)) {
            // this.date = this.parts[i].match(/-?\d.*\d-?/)[0];
            var match = this.parts[i].match(/-?\d.*\d-?/);
            // this.date = this.parts[i].substring(match.index);
            this.date = match[0];
            this.parts[i] = this.parts[i].substring(0, match.index).trim();
            if (this.parts[i] === '') {
                this.parts.splice(i, 1);
            }
            // this.date = this.parts[i].match(/-?\d.*\d-?/)[0];
            // console.log("Dated: ", this.parts);
        }
    }
};

NameParser.prototype.parseNumeration = function() {
    //get first and second

    var match = this.parts[0].match(/(.*) ([IVXCM]+ .*|[IVXCM]+$)/);
    if (match && match.length == 3) {
        this.numeration = match[2];
        this.parts[0] = match[1];
    }
};
