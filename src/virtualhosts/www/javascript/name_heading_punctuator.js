/**
 * Combine Name Heading components
 *
 * Uses implied rules from Daniel's document at https://docs.google.com/document/d/1Ld02svYzeOY0tlpWZ4zsdS0ylirZD-xH5cAzckf776w
 * to combine the name components and add punctuation.  This does NOT reorder the name components, and relies on the
 * user to use the correct name components in the correct order.  If the user does not, then punctuation is not guaranteed to
 * be correct.
 *
 * @param  String[] components Array of [type, component] pairs
 * @param  String entityType Entity type string
 * @return String              The combined name heading with punctuation
 */
function combineNameHeading(components, entityType) {
    var text = "";

    if (entityType == "person") {
        // update the components
        components.forEach(function(component, i) {
            var type = component[0];
            var partText = component[1];
            switch (type) {
                case "Numeration":
                    partText = partText + ",";
                    break;
                case "Surname":
                case "Forename":
                    // if the surname or forename are followed by a roman numeral, then don't put a comma after them
                    if (i < components.length - 1 && components[i+1][0] == 'Numeration') {
                        break;
                    }
                case "NameAddition":
                case "Date":
                    partText = partText + ",";
                    break;
                case "NameExpansion":
                    partText = "(" + partText + ")";
                    partText = partText + ",";
                    break;
            }
            components[i][1] = partText;
        });

        // assemble the name
        components.forEach(function(component) {
            text += component[1] + " ";
        });

        // remove leading and trailing spaces
        text = text.trim();
        // remove trailing "," if one exists
        if (text.endsWith(",")) {
            text = text.substring(0, text.length - 1);
        }
        text = text.trim();
        // combine multiple spaces
        text = text.replace(/\s+/g, " ");
        // replace a ", (..)" with just a " (..)"
        text = text.replace(", (", " (");

    } else if (entityType == "corporateBody") {
        // update the components
        var openedParen = false;
        components.forEach(function(component, i) {
            var type = component[0];
            var partText = component[1];
            switch (type) {
                case "Name":
                case "JurisdictionName":
                    if (i < components.length - 1 && components[i+1][0] == 'SubdivisionName') {
                        partText = partText + ".";
                    }
                    break;
                case "NameAddition":
                    partText = "(" + partText + ")";
                    partText = partText + ".";
                    break;
                case "Number":
                case "Date":
                case "Location":
                    // if it's the first one we've seen, then start a paren:
                    if (!openedParen) {
                        partText = "(" + partText;
                        openedParen = true;
                    }
                    // if it's last, then close the paren.  Else, add a colon and keep going.
                    if (i == components.length - 1) {
                        partText = partText + ")";
                    } else {
                        partText = partText + " :";
                    }
                    break;
            }
            components[i][1] = partText;
        });

        // assemble the name
        components.forEach(function(component) {
            text += component[1] + " ";
        });

        // remove leading and trailing spaces
        text = text.trim();
        // remove double periods
        text = text.replace("..", ".");
        // remove trailing "," if one exists
        if (text.endsWith(".")) {
            text = text.substring(0, text.length - 1);
        }
        text = text.trim();

    } else if (entityType == "family") {
        // update the components
        var openedParen = false;
        components.forEach(function(component, i) {
            var type = component[0];
            var partText = component[1];
            switch (type) {
                case "FamilyType":
                case "Date":
                case "ProminentMember":
                case "Place":
                    // if it's the first one we've seen, then start a paren:
                    if (!openedParen) {
                        partText = "(" + partText;
                        openedParen = true;
                    }
                    // if it's last, then close the paren.  Else, add a colon and keep going.
                    if (i == components.length - 1) {
                        partText = partText + ")";
                    } else {
                        partText = partText + " :";
                    }
                    break;
            }
            components[i][1] = partText;
        });

        // assemble the name
        components.forEach(function(component) {
            text += component[1] + " ";
        });

        // remove leading and trailing spaces
        text = text.trim();

    } else {
        // This is an error case, but act generously.
        components.forEach(function(component) {
            text += component[1] + " ";
        });
        text = text.trim();
    }


    return text;
}
