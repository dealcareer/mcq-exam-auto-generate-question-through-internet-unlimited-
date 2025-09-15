<?php
$topic = isset($_GET['topic']) ? $_GET['topic'] : '';
if (!$topic) {
    echo "Topic not provided.";
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>🧠 Topic: <?php echo htmlspecialchars($topic); ?> - MCQs</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/styles/github.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/katex@0.16.10/dist/katex.min.css">
  <style>
    body {
      background-color: #f8f9fa;
    }
    .option-btn {
      margin: 8px 0;
      width: 100%;
      text-align: left;
    }
    .correct {
      border: 2px solid #28a745 !important;
      background-color: #e9fbe9 !important;
      color: #155724 !important;
      position: relative;
    }
    .incorrect {
      border: 2px solid #dc3545 !important;
      background-color: #fdeaea !important;
      color: #721c24 !important;
      position: relative;
    }
    .correct::after {
      content: "✔ सही उत्तर";
      position: absolute;
      right: 10px;
      top: 50%;
      transform: translateY(-50%);
      font-weight: bold;
      color: #28a745;
    }
    .incorrect::after {
      content: "✘ गलत उत्तर";
      position: absolute;
      right: 10px;
      top: 50%;
      transform: translateY(-50%);
      font-weight: bold;
      color: #dc3545;
    }
    .explanation {
      display: none;
      margin-top: 10px;
    }
    .subtopic-nav {
      margin-bottom: 20px;
    }
    .subtopic-nav a {
      margin-right: 10px;
    }
    /* Styles for navigation buttons */
    .nav-arrow-container {
      position: fixed;
      right: 20px; /* Position on the right side */
      top: 50%; /* Vertically center */
      transform: translateY(-50%); /* Adjust for its own height */
      z-index: 1000;
      display: flex;
      flex-direction: column; /* Stack buttons vertically */
      gap: 10px; /* Space between buttons */
      opacity: 1; /* Start visible */
      transition: opacity 0.5s ease-in-out; /* Smooth transition for hide/show */
    }
    .nav-arrow-container.hidden {
        opacity: 0;
        pointer-events: none; /* Make it unclickable when hidden */
    }
    .nav-arrow-btn {
      background-color: #d9534f;
      color: white;
      border: none;
      border-radius: 50%;
      width: 50px;
      height: 50px;
      font-size: 24px;
      display: flex;
      align-items: center;
      justify-content: center;
      cursor: pointer;
      box-shadow: 0 2px 10px rgba(0,0,0,0.2);
    }
    .nav-arrow-btn:hover {
      background-color: none;
    }
    .nav-arrow-btn:disabled {
        background-color: #cccccc;
        cursor: not-allowed;
    }
    .option-btn {
      margin: 8px 0;
      width: 100%;
      text-align: left !important;
      padding-left: 15px; 
    }
        /* नए स्टाइल: कोड ब्लॉक और गणितीय सूत्रों के लिए */
    .card-body pre {
        white-space: pre-wrap; /* कोड को सही से रैप करने के लिए */
        background-color: #f4f4f4; /* हल्का ग्रे बैकग्राउंड */
        padding: 15px;
        border-radius: 5px;
        overflow-x: auto; /* हॉरिजॉन्टल स्क्रोलिंग के लिए */
    }
    .card-body pre code {
        white-space: pre-wrap; /* Code to wrap within the block */
    }
    .katex-display {
        margin: 1em 0; /* गणितीय सूत्रों के ऊपर-नीचे स्पेस के लिए */
        font-size: 1.2em;
    }
  </style>
</head>
<body>

<div class="container pt-3">
    <div class="alert fw-bold fs-5 text-center d-flex align-items-center justify-content-center mb-4">
        <div class="row g-3 justify-content-center w-100">
        <div class="col-12 col-md-6 d-flex">
            <div class="card shadow-sm w-100 p-3 d-flex flex-column flex-md-row align-items-center justify-content-between gap-3">
                <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-pc-display text-primary fs-4"></i>
                    <h5 class="mb-0 fw-bold">MCQ Quiz</h5>
                </div>
                <form action="take_quiz.php" method="post" class="d-flex flex-column flex-md-row align-items-center gap-2 w-100">
                    <div class="input-group flex-fill">
                        <span class="input-group-text">प्रश्न</span>
                        <input type="number" name="num_questions" class="form-control" value="10" min="1" max="100" required>
                    </div>
                    <div class="input-group flex-fill">
                        <span class="input-group-text">टाइमर</span>
                        <input type="number" name="timer_minutes" class="form-control" value="10" min="1" max="60" required>
                    </div>
                    <button type="submit" class="btn btn-primary d-flex align-items-center justify-content-center mt-2 mt-md-0 flex-shrink-0">
                        <i class="bi bi-play-fill me-1"></i> शुरू करें
                    </button>
                </form>
            </div>
        </div>
        
        <div class="col-12 col-md-6 d-flex">
            <div class="card shadow-sm w-100 p-3 d-flex flex-column flex-md-row align-items-center justify-content-between gap-3">
                <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-bar-chart-line text-success fs-4"></i>
                    <h5 class="mb-0 fw-bold">अपनी प्रगति देखें</h5>
                </div>
                <a href="status.php" class="btn btn-success d-flex align-items-center justify-content-center mt-2 mt-md-0 flex-shrink-0">
                    <i class="bi bi-eye-fill me-1"></i> स्थिति देखें
                </a>
            </div>
            <a href="index.php" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i> Back</a>
        </div>
    </div>
</div>
  <div class="mb-3 pt-5">
    <h5>🧠 Topic: <span class="text-primary"><?php echo htmlspecialchars($topic); ?></span></h5>
  </div>

  <div id="subtopic-nav" class="subtopic-nav"></div>
  <div id="mcq-container">Loading...</div>
</div>

<div class="nav-arrow-container" id="nav-arrow-container">
  <button id="scroll-top-btn" class="nav-arrow-btn" title="ऊपर जाएं"><i class="bi bi-chevron-double-up"></i></button>
  <button id="prev-question-btn" class="nav-arrow-btn" title="पिछला प्रश्न"><i class="bi bi-arrow-up"></i></button>
  <button id="next-question-btn" class="nav-arrow-btn" title="अगला प्रश्न"><i class="bi bi-arrow-down"></i></button>
  <button id="scroll-bottom-btn" class="nav-arrow-btn" title="नीचे जाएं"><i class="bi bi-chevron-double-down"></i></button>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/highlight.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/katex@0.16.10/dist/katex.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/katex@0.16.10/dist/contrib/auto-render.min.js"></script>

<script>
const topic = "<?php echo urlencode($topic); ?>";
let allQuestionCards = [];
let currentQuestionIndex = 0;

const prevBtn = document.getElementById('prev-question-btn');
const nextBtn = document.getElementById('next-question-btn');
const navArrowContainer = document.getElementById('nav-arrow-container');

let activityTimer;

// Your existing navigation functions (hideNavArrows, showNavArrows, etc.)
// ... (यह सब कोड वैसा ही रहेगा) ...
function hideNavArrows() {
    navArrowContainer.classList.add('hidden');
}

function showNavArrows() {
    navArrowContainer.classList.remove('hidden');
    clearTimeout(activityTimer);
    activityTimer = setTimeout(hideNavArrows, 5000); 
}

function updateNavigationButtons() {
    prevBtn.disabled = currentQuestionIndex === 0;
    nextBtn.disabled = currentQuestionIndex === allQuestionCards.length - 1;
}

function scrollToQuestion(index) {
    if (index >= 0 && index < allQuestionCards.length) {
        currentQuestionIndex = index;
        allQuestionCards[currentQuestionIndex].scrollIntoView({ behavior: 'smooth', block: 'center' });
        updateNavigationButtons();
        showNavArrows(); 
    }
}

prevBtn.addEventListener('click', () => {
    scrollToQuestion(currentQuestionIndex - 1);
});

nextBtn.addEventListener('click', () => {
    scrollToQuestion(currentQuestionIndex + 1);
});

window.addEventListener('scroll', () => {
    let closestQuestionIndex = 0;
    let minDistance = Infinity;

    allQuestionCards.forEach((card, index) => {
        const rect = card.getBoundingClientRect();
        const distance = Math.abs(rect.top + rect.height / 2 - window.innerHeight / 2);

        if (distance < minDistance) {
            minDistance = distance;
            closestQuestionIndex = index;
        }
    });

    if (closestQuestionIndex !== currentQuestionIndex) {
        currentQuestionIndex = closestQuestionIndex;
        updateNavigationButtons();
    }
    showNavArrows();
});

document.addEventListener('mousemove', showNavArrows);
document.addEventListener('keypress', showNavArrows);


// --- नया फ़ंक्शन: HTML कैरेक्टर को सुरक्षित रूप से एस्केप करने के लिए ---
function escapeHtml(text) {
  const map = {
    '&': '&amp;',
    '<': '&lt;',
    '>': '&gt;',
    '"': '&quot;',
    "'": '&#039;'
  };
  return text.replace(/[&<>"']/g, function(m) { return map[m]; });
}

// --- नया फ़ंक्शन: कंटेंट को सही से रेंडर करने के लिए ---
function renderContent(content) {
    // Markdown-style code blocks को `<pre><code>` में बदलें
    // इसके अंदर के कंटेंट को पहले एस्केप करें
    let renderedContent = content.replace(/```(\w+)?\n([\s\S]*?)\n```/g, (match, lang, code) => {
        const languageClass = lang ? `language-${lang}` : '';
        // कोड ब्लॉक के अंदर के HTML को एस्केप करें ताकि वह सुरक्षित रहे
        return `<pre><code class="${languageClass}">${escapeHtml(code.trim())}</code></pre>`;
    });

    // Markdown-style inline code को <code> में बदलें
    // इसके अंदर के कंटेंट को भी एस्केप करें
    renderedContent = renderedContent.replace(/`([^`]+)`/g, (match, code) => {
        return `<code>${escapeHtml(code)}</code>`;
    });

    // बाकी के कंटेंट को KaTeX के लिए छोड़ दें।
    // KaTeX खुद ही गणितीय सूत्रों को रेंडर करेगा।
    return renderedContent;
}


fetch(`fetch_topicwise_mcqs.php?topic=${topic}`)
  .then(res => res.json())
  .then(data => {
    const container = document.getElementById('mcq-container');
    const nav = document.getElementById('subtopic-nav');
    container.innerHTML = '';
    nav.innerHTML = '';
    allQuestionCards = [];

    if (!data.success) {
      container.innerHTML = '<div class="alert alert-danger">Failed to load MCQs.</div>';
      return;
    }

    const mcqsBySubtopic = data.mcqs_by_subtopic;

    let subIndex = 1;
    for (const subtopic in mcqsBySubtopic) {
      const anchorId = 'subtopic-' + subIndex;

      const navLink = document.createElement('a');
      navLink.href = `#${anchorId}`;
      navLink.className = 'btn btn-outline-success btn-sm';
      navLink.textContent = subtopic;
      nav.appendChild(navLink);

      const subtopicWrapper = document.createElement('div');
      subtopicWrapper.className = 'mb-5';
      subtopicWrapper.id = anchorId;

      const subHeader = document.createElement('h5');
      subHeader.innerHTML = `📘 <b>${subtopic}</b>`;
      subHeader.className = 'mt-4';
      subtopicWrapper.appendChild(subHeader);

      let questionCounter = 1;
      mcqsBySubtopic[subtopic].forEach((mcq) => {
        const card = document.createElement('div');
        card.className = 'card mb-4 shadow-sm question-card';
        const cardBody = document.createElement('div');
        cardBody.className = 'card-body';

        const qTitle = document.createElement('h5');
        qTitle.innerHTML = `<strong> ${questionCounter++}.</strong> ${renderContent(mcq.question_en)}`;
        const qSub = document.createElement('p');
        qSub.className = 'text-secondary';
        qSub.innerHTML = renderContent(mcq.question_hi);

        cardBody.appendChild(qTitle);
        cardBody.appendChild(qSub);

        const expDiv = document.createElement('div');
        expDiv.className = 'alert alert-info explanation';
        expDiv.innerHTML = `<i class="bi bi-info-circle"></i> ${renderContent(mcq.explanation_en)}<br><strong>📘 व्याख्या:</strong> ${renderContent(mcq.explanation_hi)}`;

        mcq.options_en.forEach((opt, i) => {
          const btn = document.createElement('button');
          btn.className = 'btn btn-outline-primary option-btn';
          
          let cleanHindiOption = mcq.options_hi[i];
          const abcdRegex = /^[A-D][.、]\s*/;
          if (abcdRegex.test(cleanHindiOption)) {
            cleanHindiOption = cleanHindiOption.replace(abcdRegex, '');
          }

          btn.innerHTML = `${renderContent(opt)}<br><small class="text-muted">${renderContent(cleanHindiOption)}</small>`;
          
          btn.dataset.answer = String.fromCharCode(65 + i);
          btn.dataset.correct = mcq.answer_en;
          btn.dataset.id = mcq.id;

          if (mcq.attempted) {
            btn.disabled = true;
            if (btn.dataset.answer === mcq.answer_en) {
              btn.classList.remove('btn-outline-primary');
              btn.classList.add('correct');
            }
            if (btn.dataset.answer === mcq.selected_option && mcq.selected_option !== mcq.answer_en) {
              btn.classList.remove('btn-outline-primary');
              btn.classList.add('incorrect');
            }
          } else {
            btn.addEventListener('click', function () {
              const allBtns = this.parentElement.querySelectorAll('.option-btn');
              allBtns.forEach(b => b.disabled = true);

              const selected = this.dataset.answer;
              const correct = this.dataset.correct;
              const mcqId = this.dataset.id;

              if (selected === correct) {
                this.classList.remove('btn-outline-primary');
                this.classList.add('correct');
              } else {
                this.classList.remove('btn-outline-primary');
                this.classList.add('incorrect');
                const correctBtn = Array.from(allBtns).find(b => b.dataset.answer === correct);
                if (correctBtn) correctBtn.classList.add('correct');
              }

              expDiv.style.display = 'block';

              fetch('save_attempt.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                  mcq_id: mcqId,
                  selected_option: selected,
                  correct_option: correct,
                  user_ip: '',
                  user_agent: navigator.userAgent,
                  mac_address: 'N/A'
                })
              });
            });
          }

          cardBody.appendChild(btn);
        });

        if (mcq.attempted) expDiv.style.display = 'block';
        cardBody.appendChild(expDiv);
        card.appendChild(cardBody);
        subtopicWrapper.appendChild(card);
        allQuestionCards.push(card);
      });

      container.appendChild(subtopicWrapper);
      subIndex++;
    }

    // --- नया लॉजिक: कंटेंट लोड होने के बाद हाइलाइटिंग लागू करें ---
    document.querySelectorAll('pre code').forEach(el => {
        // अगर कोई भाषा निर्दिष्ट नहीं है तो C को डिफ़ॉल्ट मान लें
        if (!el.className) {
            el.className = 'language-c';
        }
        hljs.highlightElement(el);
    });

    // गणितीय सूत्रों को रेंडर करें
    renderMathInElement(document.body, {
        delimiters: [
            {left: '$$', right: '$$', display: true}, // ब्लॉक फ़ॉर्मूला के लिए
            {left: '$', right: '$', display: false}, // इनलाइन फ़ॉर्मूला के लिए
        ]
    });

    updateNavigationButtons();
    if (allQuestionCards.length > 0) {
        scrollToQuestion(0);
    }
    showNavArrows();
  })
  .catch(err => {
    document.getElementById('mcq-container').innerHTML = '<div class="alert alert-danger">Error loading MCQs.</div>';
    console.error(err);
  });
  
  const scrollTopBtn = document.getElementById('scroll-top-btn');
  const scrollBottomBtn = document.getElementById('scroll-bottom-btn');

scrollTopBtn.addEventListener('click', () => {
  window.scrollTo({ top: 0, behavior: 'smooth' });
  showNavArrows();
});

scrollBottomBtn.addEventListener('click', () => {
  window.scrollTo({ top: document.body.scrollHeight, behavior: 'smooth' });
  showNavArrows();
});
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>