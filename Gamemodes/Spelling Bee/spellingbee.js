let word;

const words = [
"aberration","abstemious","acumen","alacrity","amalgamate",
"amenable","anachronism","anomaly","antipathy","approbation",
"arduous","asceticism","assiduous","astringent","atrophy",
"austere","avarice","axiom","bolster","burgeon","burnish",
"cacophony","capricious","castigate","catalyst","caustic",
"chicanery","circumlocution","circumscribe","circumspect",
"coalition","complaisance","connoisseur","contentious",
"contrite","conundrum","convoluted","corporeal","credulous",
"culpable","debacle","decorum","deference","derision",
"desiccate","didactic","dilatory","diligent","dirge",
"disabuse","discordant","discretion","disinterested",
"disparage","disparate","dissemble","dissonance","dogmatic",
"eclectic","efficacy","effrontery","ephemeral","equivocate",
"erudite","esoteric","exacerbate","exonerate","facetious",
"fallacious","frugal","garrulous","gregarious","hedonism",
"hyperbole","idiosyncrasy","impecunious","inchoate","indolent",
"inscrutable","inundate","irascible","laconic","magnanimity",
"mendacity","nebulous","nefarious","obdurate","obfuscate",
"obsequious","onerous","pellucid","perfidious","pragmatic",
"precarious","prescient","profligate","punctilious",
"quiescent","recalcitrant","reticent","sagacious",
"sanguine","stoic","succinct","taciturn","ubiquitous",
"vexation","vilify","virulent","voluble","waver"
];

function speakWord() {
    const voice = new SpeechSynthesisUtterance(word);
    speechSynthesis.speak(voice);
}

document.getElementById('btn').addEventListener('click', () => {
    word = words[Math.floor(Math.random() * words.length)];
    speakWord();
    document.getElementById('inp').value = '';
    document.getElementById('inp').focus();
});

document.getElementById('speak').addEventListener('click', () => {
    if (word) speakWord();
});

document.getElementById('sub').addEventListener('click', () => {
    const answer = document.getElementById('inp').value.trim();
    if (!word) return;

    if (answer === word) {
      alert("✅ CORRECT!");
    } else {
      alert(`❌ INCORRECT\nThe correct word was: ${word}`);
    }

    document.getElementById('inp').value = '';
});
