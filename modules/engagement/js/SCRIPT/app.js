function addQuestion(){
    const wrapper = document.getElementById('questions');
    const idx = wrapper.children.length;
    const html = `<div class="question-block">
        <input type="text" name="questions[${idx}][question_text]" placeholder="Question text" required>
        <select name="questions[${idx}][type]"><option value="text">Text</option><option value="rating">Rating</option></select>
    </div>`;
    wrapper.insertAdjacentHTML('beforeend', html);
}
