<div style="
                    padding:16px 26px;
                    border-top:1px solid #e2e8f0;
                    display:flex;
                    justify-content:space-between;
                    align-items:center;
                    gap:12px;
                    background:#ffffff;
                ">

    <button type="button" id="courseBackButton" onclick="previousCourseStep()" style="
        display:none;
        align-items:center;
        justify-content:center;
        gap:8px;
        height:42px;
        min-width:88px;
        padding:0 16px;
        border:1px solid #e2e8f0;
        border-radius:10px;
        background:#ffffff;
        color:#475569;
        font-size:13px;
        font-weight:600;
        line-height:1;
        cursor:pointer;
        box-shadow:0 1px 2px rgba(15,23,42,.04);
        transition:
            background .2s ease,
            border-color .2s ease,
            color .2s ease,
            box-shadow .2s ease,
            transform .15s ease;
    " onmouseover="
        this.style.background='#f8fafc';
        this.style.borderColor='#cbd5e1';
        this.style.color='#1e293b';
        this.style.boxShadow='0 2px 5px rgba(15,23,42,.08)';
    " onmouseout="
        this.style.background='#ffffff';
        this.style.borderColor='#e2e8f0';
        this.style.color='#475569';
        this.style.boxShadow='0 1px 2px rgba(15,23,42,.04)';
    " onmousedown="
        this.style.transform='scale(.97)';
    " onmouseup="
        this.style.transform='scale(1)';
    ">
        <i class="fa-solid fa-arrow-left" style="font-size:12px;"></i>
        <span>Back</span>
    </button>


    <div style="margin-left:auto;display:flex;gap:9px;">

        <button type="button" onclick="closeCreateCourseModal()" style="
                            height:42px;
                            padding:0 17px;
                            border:1px solid #cbd5e1;
                            border-radius:10px;
                            background:#fff;
                            color:#475569;
                            font-size:13px;
                            font-weight:600;
                            cursor:pointer;
                        ">
            Cancel
        </button>

        <button type="button" id="courseNextButton" onclick="nextCourseStep()" style="
        height:42px;
        padding:0 18px;
        border:none;
        border-radius:10px;
        background:#2563eb;
        color:#fff;
        font-size:13px;
        font-weight:600;
        cursor:pointer;
        display:inline-flex;
        align-items:center;
        justify-content:center;
        gap:8px;
    ">
            <span>Next</span>
            <i class="fa-solid fa-arrow-right"></i>
        </button>

    </div>

</div>