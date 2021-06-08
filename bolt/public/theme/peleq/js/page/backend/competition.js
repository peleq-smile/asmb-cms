function onScoreTyping($scoreInput) {
    let scoreValue = $scoreInput.val(),
        regexp = new RegExp('^[0-9 ]$');

    if (regexp.test(scoreValue)) {
        // Seulement des chiffres et des espaces : on peut ajouter automatiquement les slashs
        scoreValue = scoreValue.replace(/(\d)(\d)/, '$1/$2 ')
            .replace(/(\d)(\d)/, '$1/$2 ')
            .replace(/(\d)(\d)/, '$1/$2 ')
            .replace(/' '{2,}/, ' ')
            .trim()
        ;
        $scoreInput.val(scoreValue);
    }
}

function onRankingTyping($rankingInput) {
    let rankingValue = $rankingInput.val(),
        nbChar = rankingValue.length;

    if (rankingValue && $.isNumeric(rankingValue)) {
        if (nbChar == 3) { //ex: 305, 155, -26, -46
            $rankingInput.val(rankingValue[0] + rankingValue[1] + '/' + rankingValue[2]);
        } else if ($.inArray(rankingValue, ['56','46','36','26','16']) >= 0) {
            $rankingInput.val(rankingValue[0] + '/' + rankingValue[1]);
        }
    }
}