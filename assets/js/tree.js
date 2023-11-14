(function ($) {
    // Структуриране на JSON данните
    let jsonData = {} /* вашите JSON данни тук */

    // Дефиниране на елементите
    let $questionTitle = $("#quiz-title");
    let $buttonsContainer = $(".buttons-container");
    let $progressBar = $(".progress-bar");
    let totalQuestions = 6;
    let currentQuestion = 0;
    let questionStack = [];
    let $questionsBox = $("#questions-box");
    let $resultBox = $("#result-box");
    let $resultBoxContent = $("#question-content");
    let nextQuestion = null;
    let $selected_option = $('#selected_option');
    let $new_income = $('#new_income');
    let change_button = [];
    let $backButton = $("#back-button");
    $('#test').css('display', 'none');

    // add style="width: fit-content;" on all a hrefs with class="btn-question"
    $('a.btn-question').attr('style', 'width: fit-content;');


    let people_number = $('#people_number');
    people_number.val(0)
    $selected_option.val(0);
    $new_income.val(0);

    let selected_option = 0;
    let income = 0;


    $.ajax({
        url: build_tree.build_tree_url,
        type: 'POST',
        async: false,
        data: {
            action: "get_tree",
        }, success: function (data) {
            nextQuestion = JSON.parse(data);
        }
    });

    let math_json;
    $.ajax({
        async: false,
        url: build_tree.build_tree_url,
        type: 'POST',
        cache: false,
        data: {
            action: "get_tree_math",
        }, success: function (data) {
            math_json = JSON.parse(data)
        }
    });


    function progress_increase() {
        currentQuestion++;
        let progress = (currentQuestion / totalQuestions) * 100;
        $progressBar.val(progress);
    }

    function progress_decrease() {
        currentQuestion--;
        let progress = (currentQuestion / totalQuestions) * 100;
        $progressBar.val(progress);
    }

    function fadeOutAndIn(element, html) {
        return new Promise((resolve) => {
            element.fadeOut(100, function () {
                $(this).html(html).fadeIn(100, function () {
                    resolve();
                });
            });
        });
    }

    $backButton.on('click', function () {
        $(this).prop('disabled', questionStack.length >= 1).show();
        if (questionStack.length > 0) {
            // Pop the last question from the stack
            questionStack.pop();

            // Ask the new last question in the stack
            if (questionStack.length > 0) {
                askQuestion(questionStack[questionStack.length - 1], false);
            }

            // $(this).prop('disabled', questionStack.length < 1);

            progress_decrease();
        }
    });

    function decodeHtml(html) {
        html = html.replace(/<\/?pre[^>]*>/g, '');
        let txt = document.createElement("textarea");
        txt.innerHTML = html;
        return txt.value;
    }

    function showChildren() {
        return new Promise((resolve) => {
            resolve();
        })
    }

    function number_of_people($number) {
        const data = {
            1: {
                1: 27250,
                2: 32400,
                3: 32400,
            },
            2: {
                1: 50635,
                2: 67000,
                3: 67000,
            },
            3: {
                1: 50635,
                2: 86000,
                3: 86000,
            },
            4: {
                1: 50635,
                2: 105000,
                3: 105000,
            },
            5: {
                1: 72113,
                2: 110000,
                3: 110000,
            },
        }

        return data[$number] || null;
    }


    function askQuestion(data, push = true) {
        if (push) {
            questionStack.push(data);
        }

        if (data.type === 'math') {
            askQuestion(math_json, false);
        }


        // WRITE QUEST
        $backButton.prop('disabled', questionStack.length <= 1).hide();
        if (questionStack.length <= 1) {
            $backButton.hide();
        } else {
            $backButton.show();
        }

        if (data.type === 'result') {
            return;
        }

        if (!data.children || data.children.length === 0) {
            return;
        }

        let cleanedContent = data.content.replace(/<\/?pre[^>]*>/g, '');
        $resultBoxContent.html(decodeHtml(cleanedContent));

        $questionTitle.text(data.title);

        $buttonsContainer.empty();

        // WRITE CHILDREN

        // await showChildren();

        for (let index = 0; index < data.children.length; index++) {
            let child = data.children[index];
            let count = index + 1;

            let $button = $(`<button id="${count}" class="btn-question"></button>`);

            if (change_button.hasOwnProperty('fill') && ["true"].includes(change_button.fill)) {
                $button = $(`<button class="btn-question"></button>`)
                $questionTitle.text(change_button.title);
                $button.text("NEXT");
            } else {
                $button.text(child.title);
            }

            if (child.hasOwnProperty('income_category') && child.income_category === 'choice_income') {
                let income;
                let id = index + 1
                if (people_number.val() > 5) {
                    income = number_of_people(5);
                    income = income[id];
                } else {
                    income = number_of_people(people_number.val());
                    income = income[id];
                }

                $button.attr('category', income);
            }

            if (['HOW MANY PEOPLE LIVE IN YOUR HOUSE?'].includes(data.title)) {
                $buttonsContainer.append(`<input id="change-number-2" type="number" placeholder="Enter number">`)
                // $button = $('<button id="number_of_people" class="btn-question">NEXT</button>')
                $button.add('id', 'number_of_people')
                $button.text("NEXT");
                $button.attr('disabled', true)
                $buttonsContainer.append($button);

                $('#change-number-2').on('input', function () {
                    // remove - from number
                    $(this).val($(this).val().replace(/-/g, ''));


                    if ($(this).val() >= 1) {
                        $('#people_number').val(Number($(this).val()));
                        $button.attr('disabled', false)
                    } else {
                        $button.attr('disabled', true)
                    }
                })

                $button.on('click', function () {
                    progress_increase();
                    askQuestion(child);
                })
                return;
            }


            $button.on('click', function () {
                if ($(this).attr('category')) {
                    $selected_option.val($(this).attr('category'));
                }

                // if (child.type === 'result' && child.type !== 'math') {
                //     $questionsBox.removeClass('active');
                //     $resultBox.addClass('active');
                //     writeResult(child);
                //     displayContent(child)
                //     return;
                // }

                let nextNonFinalChild = child.children.find(c => c.children && c.children.length > 0);

                if (nextNonFinalChild) {
                    askQuestion(nextNonFinalChild);
                } else {
                    data.children.forEach(c => {
                        let math = Math.abs(Number($selected_option.val()) - Number($new_income.val()));
                        if ($selected_option.val()) {
                            selected_option = math
                            income = $selected_option.val()
                        }

                        if (c.hasOwnProperty('income') && c.income === 'lower' && math <= $selected_option.val()) {
                            // $questionsBox.removeClass('active');
                            // $resultBox.addClass('active');
                            writeResult(c);
                            displayContent(c)
                            return;
                        }

                        if (c.hasOwnProperty('income') && c.income === 'higher' && math > $selected_option.val()) {
                            // $questionsBox.removeClass('active');
                            // $resultBox.addClass('active');
                            writeResult(c);
                            displayContent(c)
                            return;
                        }
                    })

                    child.children.find(function (c) {
                        if (c.type === "result" && c.type !== "math" && !c.hasOwnProperty('number_of_people')) {
                            // $questionsBox.removeClass('active');
                            // $resultBox.addClass('active');
                            writeResult(c);
                            displayContent(c)
                            return;
                        }

                        if (c.type === 'result' && c.type !== 'math' && c.hasOwnProperty('number_of_people')) {
                            if (c.hasOwnProperty('number_of_people') && c.number_of_people === 2 && people_number.val() <= 2) {
                                // $questionsBox.removeClass('active');
                                // $resultBox.addClass('active');
                                writeResult(c);
                                displayContent(c)
                                return;
                            }
                            if (c.hasOwnProperty('number_of_people') && c.number_of_people === 3 && people_number.val() >= 3) {
                                // $questionsBox.removeClass('active');
                                // $resultBox.addClass('active');
                                writeResult(c);
                                displayContent(c)
                                return;
                            }

                        }

                        askQuestion(c);
                    })
                }

                progress_increase();
            })

            if (["WHAT IS YOUR TOTAL PAYABLE? (THIS CAN BE FOUND ON LINE 435 OF YOUR NOTICE OF ASSESSMENT)"].includes(data.title)) {
                $buttonsContainer.append(`<input id="change-number-1" type="number" placeholder="Enter number">`)
                $button.add('id', 'number_of_people')
                $button.text("NEXT");
                $button.attr('disabled', true)
                $buttonsContainer.append($button);

                $('#change-number-1').on('input', function () {
                    $(this).val($(this).val().replace(/-/g, ''));

                    if ($(this).val() >= 1) {
                        $button.attr('disabled', false)
                    } else {
                        $button.attr('disabled', true)
                    }

                    $new_income.val(Number($(this).val()));
                })
                return;
            }

            // DISPLAY BUTTONS

            if (child.title) {
                $buttonsContainer.append($button);
            }

            if (child.hasOwnProperty('income_category') && child.income_category === 'choice_income') {
                let span = $('<span id="span-text"></span>');
                span.append($button);
                $buttonsContainer.append(span);

                let USDollar = new Intl.NumberFormat('en-US', {
                    style: 'currency',
                    currency: 'USD',
                });

                let spa_id = index + 1
                income = number_of_people(people_number.val() > 5 ? 5 : people_number.val());
                let text
                let amount = 0;
                if (index === 0) {
                  
                    amount = USDollar.format(income[spa_id]).replace(/\.00$/, '')
                      console.log(amount)
                    text = `less than ${amount}`    
                } else if (index === 1) {
                        console.log
                    let amount_minus = USDollar.format(income[spa_id-1]).replace(/\.00$/, '')
                    let amount_plus = USDollar.format(income[spa_id+1]).replace(/\.00$/, '')
                    text = `between ${amount_minus}  - ${amount_plus}`
                } else if (index === 2) {
                    amount = USDollar.format(income[spa_id]).replace(/\.00$/, '')
                    text = `greater than ${amount}`
                }
                $button.text(text.toUpperCase())

                // span.append(`<p style="text-align: center;font-size: 14px;margin-top: 5px;" id="income-text">${text}</p>`);
            }
        }

        function displayContent(child) {
            cleanedContent = child.content.replace(/<\/?pre[^>]*>/g, '');
            $resultBoxContent.html(decodeHtml(cleanedContent))
            $questionsBox.removeClass('active');
            $resultBox.addClass('active');
        }

        function writeResult(child) {
            $.ajax({
                url: build_tree.build_tree_url,
                type: 'POST',
                data: {
                    action: "write_result",
                    security: build_tree.ajax_nonce,
                    title: child.title,
                    income: Number(income),
                    income_payable: selected_option,
                    payable: $new_income.val(),
                }
            })
        }
    }

    function resetQuiz() {
        currentQuestion = 0;
        questionStack = [];
        $progressBar.val(0);
        $questionsBox.addClass('active');
        $resultBox.removeClass('active');
        $questionTitle.text('');
        $resultBoxContent.text('');
        $buttonsContainer.empty();  // Remove the old buttons
        nextQuestion = null;  // Reset the nextQuestion variable
    }

    function startQuiz() {
        nextQuestion
    }

    let $resetButton = $("#reset-form");
    $resetButton.on('click', function () {
        $('#people_number').val('');
        $selected_option.val('');
        $new_income.val('');
        resetQuiz();
        window.location.reload();
    });


    // startQuiz();

    setInterval(function () {
        // Ако има следващ въпрос, задайте го и изчистете nextQuestion
        if (nextQuestion) {
            askQuestion(nextQuestion);
            nextQuestion = null;
        }
    }, 100);


    function createSelect() {
        /* Look for any elements with the class "custom-select": */
        let select = document.querySelector("select.custom-select");
        let b = "";
        let c = "";
        for (i = 0; i < select.length; i++) {
            b = `<div class="custom-option" data-value="${select[i].value}">${select[i].innerHTML}</div>`;
            c += b;
        }
        let a = document.createElement("DIV");
        a.setAttribute("class", "select-selected");
        a.innerHTML = "Select Value:";
        document.querySelector("div.custom-select").appendChild(a);
        let d = document.createElement("DIV");
        d.setAttribute("class", "select-items select-hide");
        d.innerHTML = c;
        document.querySelector("div.custom-select").appendChild(d);
        a.addEventListener("click", function (e) {
            $('.select-items').toggleClass('select-hide');
            /* When the select box is clicked, close any other select boxes,
                     and open/close the current select box: */
            e.stopPropagation();
            closeAllSelect(this);
            // this.nextSibling.classList.toggle("select-hide");
            this.classList.toggle("select-arrow-active");
        });

        document.querySelectorAll(".custom-option").forEach((element) =>
            element.addEventListener("click", function (e) {
                let value = element.getAttribute("data-value");
                const optionTag = document.querySelector(`option[value="${value}"]`);
                optionTag.selected = true;

                const selectElement = optionTag.parentElement; // Assuming the option is inside a <select> element
                optionTag.selected = true;

                const changeEvent = new Event("change", {bubbles: true});
                selectElement.dispatchEvent(changeEvent);
            })
        );
    }

    function closeAllSelect(elmnt) {
        /* A function that will close all select boxes in the document,
              except the current select box: */
        let x,
            y,
            i,
            xl,
            yl,
            arrNo = [];
        x = document.getElementsByClassName("select-items");
        y = document.getElementsByClassName("select-selected");
        xl = x.length;
        yl = y.length;
        for (i = 0; i < yl; i++) {
            if (elmnt == y[i]) {
                arrNo.push(i);
            } else {
                y[i].classList.remove("select-arrow-active");
            }
        }
        for (i = 0; i < xl; i++) {
            if (arrNo.indexOf(i)) {
                x[i].classList.add("select-hide");
            }
        }
    }
})(jQuery);