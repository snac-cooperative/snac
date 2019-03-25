/**
 * Name Parser
 *
 * @author Robbie Hott, Joseph Glass
 * @license https://opensource.org/licenses/BSD-3-Clause BSD 3-Clause
 * @copyright 2018 the Rector and Visitors of the University of Virginia
 */


/**
 * Name Parser
 *
 * Javascript Name Parser
 *
 */
var NameParser = function() {
};


/**
 * Guess Person
 *
 * Takes a person name string and parses it into several possible arrangements
 *
 * @param string name The person name
 * @return object[] Array of Javascript Name Parser Name objects
 */
NameParser.prototype.guessPerson = function(name) {
    name = this.parsePerson(name)
    var firstParse = name.parsed
    var clonedParse = Object.assign({}, firstParse)

    // Guess Surname and Forename
    if (!name.parsed["Surname"] && name.parsed["Forename"].match(/ /)) {
        var newClone = Object.assign({}, clonedParse)
        var forenameWithSpace = newClone["Forename"]
        lastSpace = forenameWithSpace.lastIndexOf(' ')
        newClone["Surname"] = forenameWithSpace.slice(0, lastSpace).trim()
        newClone["Forename"] = forenameWithSpace.slice(lastSpace).trim()

        flippedNames = Object.assign({}, newClone)
        flippedNames["Surname"] = newClone["Forename"]
        flippedNames["Forename"] = newClone["Surname"]

        name.guesses.push(newClone);
        name.guesses.push(flippedNames);
    }

    // if multiple name additions, add guess with them combined
    if (name.parsed["NameAdditions"] > 1) {
        var newClone = Object.assign({}, clonedParse)
        newClone["NameAdditions"] = newClone["NameAdditions"].join(' ');
        name.guesses.push(newClone);
    }

    return name.guesses;
};


/**
 * Parse Person
 *
 * Parses a person name string into name components.
 *
 * @param string name The person name
 * @return Name Javascript parser name object
 */
NameParser.prototype.parsePerson = function(name) {
    name = new Name(name)
    this.parseDate(name);
    this.parseNumeration(name);
    var length = name.parts.length;

    if (length == 1) {        // If there is only one name part, it defaults to forename
        name.parsed["Forename"] = name.parts[0];
        return name;
    }

    for (var i = 0; i < length; i++) {
        var part = name.parts[i];
        var lowered = part.toLowerCase();
        if (i === 0) {
            name.parsed["Surname"] = part;    // First part is assumed to be surname
            continue;
        }

        if (i === 1) {
            if (part.startsWith('(')) {
                name.parsed["NameAdditions"].push(part);
            }
            else if (lowered.includes("emperor") ||
                    lowered.includes("empress") ||
                    lowered.includes("king") ||
                    lowered.includes("queen") ||
                    lowered.includes("prince") ||
                    lowered.includes("chief")) {
                name.parsed["NameAdditions"].push(part);
            }
            else {
                name.parsed["Forename"] = part;
            }
        } else if (name.parts[i - 1] === name.parsed["Forename"] && part.startsWith('(')) {
            name.parsed["NameExpansion"] = part.replace(/\(|\)/g, '');
        } else {
            name.parsed["NameAdditions"].push(part.replace(/\(|\)/g, '')); // Anything not known is officially a name addition

        }
    }
    // if there's only one name, it should default to forename, not surname
    if (name.parsed["Forename"] === undefined && name.parsed["Surname"]) {
        name.parsed["Forename"] = name.parsed["Surname"];
        delete name.parsed["Surname"]
    }
    return name;

};

/**
 * Parse Date
 *
 * Parses a date out of a Name object
 *
 * @param  Name  name
 * @return string Date string
 */
NameParser.prototype.parseDate = function(name) {
    for (var i=0; i < name.parts.length; i++) {
        if (name.parts[i].match(/\d\d+|\d+\s*-|-\s*\d+|\d+\s*-\s*\d+/)) {
            var match = name.parts[i].match(/-?\d\d.*\d-?/);
            if (match) {
                name.parsed["Date"] = match[0];
                name.parts[i] = name.parts[i].substring(0, match.index).trim();
                if (name.parts[i] === '') {
                    name.parts.splice(i, 1);
                }
            }
        }
    }
    return name.parsed["Date"];
};


// Numeration is for titles, , For generational suffix, use nameAdditon
// e.g.  Alexander I => Numeration: I,  Pope John Paul II => Numeration: II
// e.g.  Alexander I => Numeration: I.

/**
* Parse Numeration
*
* Parses a numeration out of a Name object
*
* @param  Name  name object
* @return string Numeration string
*/
NameParser.prototype.parseNumeration = function(name) {
    var match = name.parts[0].match(/(.*) ([IVXCM]+ .*|[IVXCM]+$)/);
    if (match && match.length == 3) {
        name.parsed["Numeration"] = match[2];
        name.parts[0] = match[1];
    }
};

/**
 * Name
 *
 * Name object for Javascript Name Parser
 *
 * @param  string  name string
 */
var Name = function(original) {
    this.original = original || ''
    this.parts = this.splitName(original);
    this.parsed = { "Surname" : null,
                "Forename" : null,
                "NameExpansion" : null,
                "Numeration" : null,
                "NameAdditions" : [],
                "Date" : null
            }
    this.guesses = [this.parsed]
}

Name.prototype.splitName = function(original) {
    var result = original.split(/,|(\(.*?\))/).map( function(part) {
            if (part) {
                return part.trim();
            }
    });
    return result.filter(function(e) {return e;});
};
