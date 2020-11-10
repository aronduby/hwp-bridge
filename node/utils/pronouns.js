const pronouns = {
    "he": {
        "subject": "he",
        "object": "him",
        "possessive": "his",
        "possessivePlural": "his",
        "reflective": "himself"
},
    "she": {
        "subject": "she",
        "object": "her",
        "possessive": "her",
        "possessivePlural": "hers",
        "reflective": "herself"
},
    "they": {
        "subject": "they",
        "object": "them",
        "possessive": "their",
        "possessivePlural": "theirs",
        "reflective": "themself"
},
    "ae": {
        "subject": "ae",
        "object": "aer",
        "possessive": "aer",
        "possessivePlural": "aers",
        "reflective": "aerself"
},
    "ey": {
        "subject": "ey",
        "object": "em",
        "possessive": "eir",
        "possessivePlural": "eirs",
        "reflective": "eirself"
},
    "fae": {
        "subject": "fae",
        "object": "faer",
        "possessive": "faer",
        "possessivePlural": "faers",
        "reflective": "faerself"
},
    "per": {
        "subject": "per",
        "object": "per",
        "possessive": "pers",
        "possessivePlural": "pers",
        "reflective": "perself"
},
    "ve": {
        "subject": "ve",
        "object": "ver",
        "possessive": "vis",
        "possessivePlural": "vis",
        "reflective": "verself"
},
    "x": {
        "subject": "x",
        "object": "x",
        "possessive": "x",
        "possessivePlural": "xs",
        "reflective": "xself"
},
    "xe": {
        "subject": "xe",
        "object": "xem",
        "possessive": "xyr",
        "possessivePlural": "xyrs",
        "reflective": "xemself"
},
    "ze": {
        "subject": "ze",
        "object": "hir",
        "possessive": "hir",
        "possessivePlural": "hirs",
        "reflective": "hirself"
}
};

function playerPronouns(player, type) {
    return pronouns[player.pronouns][type];
}

module.exports = {
    pronouns, playerPronouns
}