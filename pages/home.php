<script src="https://unpkg.com/gojs@2.3.8/release/go.js"></script>

<div class="wpbody-content" style="position: relative;">
    <div id="info"></div>


    <!-- The Modal -->
    <div id="myModal" class="modal"></div>

    <div id="diagramDiv" style="width:100%; height:90vh;"></div>

    <div class="treeOperationButtons">
        <button id="download-json" class="action-button">Export JSON</button>
        <label for="fileInput" class="action-button file-upload-label">
            Import JSON
            <input id="file" type="file" id="fileInput" accept=".json"/>
        </label>
    </div>
</div>

<script>
    (function ($) {
        //global variables
        let nodeTitle = '';
        let selectChoice = 'quest';
        let nodeContent = '';
        let jsonData;
        let keyCounter = 0;

        function updateJsonFile(updatedJSON) {
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: "build_tree",
                    jsonData: JSON.stringify(updatedJSON, null, 2)
                }
            });
        }

        function showModal(existingTitle = '', existingContent = '', existingChoice = 'quest') {
            return new Promise((resolve) => {

                let modal = $('#myModal');
                modal.css('display', 'block');

                modal.html(`
            <div class="modal-content">
                <span class="plugin-modal-close">&times;</span>
                <input type="text" id="node-title" placeholder="Enter node title" value="${existingTitle}" />
                <div class="">
                    <select id="select-choice">
                        <option value="quest">Question</option>
                        <option value="choice">Choice</option>
                        <option value="result">Result</option>
                        <option value="math">Math</option>
                    </select>
                </div>
                <div id="div_editor1">
                    ${existingContent}
                </div>
                <button id="modalButton">Save</button>
            </div>
        `);

                $(".plugin-modal-close").on('click', function () {
                    modal.html('').css('display', 'none');
                    // stop action here
                    return;
                });

                // Set the selected option
                $('#select-choice').val(existingChoice);

                // Quill.register("modules/htmlEditButton", htmlEditButton);

                // let modules = {
                //     htmlEditButton: {
                //         debug: false,
                //         msg: "Edit the content in HTML format",
                //         okText: "Ok",
                //         cancelText: "Cancel",
                //         buttonHTML: "&lt;&gt;",
                //         buttonTitle: "Show HTML source",
                //         syntax: false,
                //         prependSelector: 'div#myelement',
                //         editorModules: {}
                //     }
                // };
                //
                // // Initialize the first editor
                // const firstEditor = document.querySelector('#editor');
                // const quill = new Quill(firstEditor, {
                //     modules: modules,
                //     theme: 'snow'
                // });


                let toolbarOptions = [
                    ['bold', 'italic', 'underline', 'strike'],        // toggled buttons
                    ['blockquote', 'code-block'],
                    [{'list': 'ordered'}, {'list': 'bullet'}],
                    [{'header': 1}, {'header': 2}],               // custom dropdown
                    [{'color': []}, {'background': []}],          // dropdown with defaults from theme
                    [{'font': []}],
                    [{'align': []}],
                    ['clean'],                                         // remove formatting button
                ];

                let selectElement = document.createElement('select');
                let option1 = document.createElement('option');
                option1.value = "option1";
                option1.text = "Option1";
                let option2 = document.createElement('option');
                option2.value = "option2";
                option2.text = "Option2";


                let editor1 = new RichTextEditor("#div_editor1");
                // let quill = new Quill(firstEditor, {
                //     modules: {
                //         toolbar: toolbarOptions
                //     },
                //     theme: 'snow'
                // });

                $('#modalButton').on('click', function () {
                    selectChoice = $('#select-choice').val();
                    nodeTitle = $('#node-title').val();
                    nodeContent = editor1.getHTMLCode();

                    modal.html('').css('display', 'none');
                    resolve();
                });

            });
        }


        let gogo = go.GraphObject.make;
        const diagram = gogo(go.Diagram, "diagramDiv");

        function updateNodeCoordinatesInJSON(json, key, x, y) {
            if (!json || typeof json.key === 'undefined') {
                return false; // If json is undefined or doesn't have a key property, return
            }

            if (json.key === key) {
                json.x = x;
                json.y = y;
                console.log('Node found and updated:', key, x, y); // Log the updated values
                return true; // Successfully updated
            }

            if (json.children) {
                for (let child of json.children) {
                    if (updateNodeCoordinatesInJSON(child, key, x, y)) {
                        return true; // Successfully updated in one of the child nodes
                    }
                }
            }
            return false; // Couldn't find a matching key in this subtree
        }

        diagram.nodeCategoryProperty = "category";

        diagram.addDiagramListener("SelectionMoved", function (e) {
            e.subject.each(function (part) {
                if (part instanceof go.Node) { // check if it is actually a node
                    const location = part.location; // get the new location
                    // console.log('Node moved:', location);
                    // // Log the values to see if they are being detected correctly
                    // console.log('Node moved:', part.data.key);
                    // console.log('New Location:', location.x, location.y);

                    // Update the part's coordinates in your JSON structure
                    const key = part.data.key;
                    const x = location.x;
                    const y = location.y;
                    const updated = updateNodeCoordinatesInJSON(updatedJSON, key, x, y);
                    // console.log(updatedJSON)

                    // console.log('JSON updated:', updated); // Log whether the JSON was updated or not

                    // After updating your JSON structure, you can then save it
                    updateJsonFile(updatedJSON);
                }
            });
        });

        let updatedJSON = jsonData;

        function findTreeRoot(diagram) {
            let rootNode = null;
            diagram.nodes.each(node => {
                const linksInto = node.findLinksInto();
                if (linksInto.count === 0) {
                    rootNode = node;
                    return;
                }
            });
            return rootNode;
        }

        function createJSONstructure(node) {
            const json = Object.assign({}, node.data);  // copy all properties


            json.key = node.data.key
            json.title = node.data.text
            json.type = node.category
            json.content = node.data.content
            json.x = node.location.x
            json.y = node.location.y
            json.children = []

            const childLinks = diagram.findLinksByExample({from: node.key});
            while (childLinks.next()) {
                const link = childLinks.value;
                const childNode = link.toNode;
                if (childNode) {
                    json.children.push(createJSONstructure(childNode));
                }
            }

            return json;
        }

        function createNodeTemplate(backgroundColor) {
            return gogo(go.Node, "Spot",
                gogo(go.Panel, "Auto",
                    gogo(go.Shape, "Rectangle", {fill: backgroundColor, stroke: null, stretch: go.GraphObject.Fill}),
                    gogo(go.TextBlock,
                        {
                            margin: 25,
                            textAlign: "center",
                            font: "bold 12pt Arial",
                            minSize: new go.Size(120, NaN),
                            editable: true,
                            textEdited: function (textBlock, previousText, currentText) {
                                // Update the node's text when it's edited
                                textBlock.diagram.model.setDataProperty(textBlock.part.data, "text", currentText);
                            }
                        },
                        new go.Binding("text", "text").makeTwoWay()
                    )
                ),
                gogo("Button",  // '+' button
                    {
                        alignment: go.Spot.BottomLeft,
                        click: async function (e, obj) {  // click event handler
                            const parentNode = obj.part;

                            // wait for the user to enter a new node name
                            await showModal();

                            const nodeKey = selectChoice + "-" + keyCounter++;

                            const childNode = {
                                key: nodeKey,
                                text: nodeTitle,
                                content: nodeContent,
                                category: selectChoice

                            };

                            const link = {
                                from: parentNode.data.key,
                                to: childNode.key
                            };

                            // Add new node and link to model
                            diagram.startTransaction();
                            diagram.model.addNodeData(childNode);
                            diagram.model.addLinkData(link);
                            diagram.commitTransaction("Add Child");

                            // Update the JSON structure
                            const root = diagram.findNodeForKey(parentNode.findTreeRoot().key);
                            updatedJSON = createJSONstructure(root);

                            updateJsonFile(updatedJSON);
                        }
                    },
                    gogo(go.TextBlock, "+", {
                        font: "bold 12pt Arial",
                        background: "#4CAF50", // Green background
                        desiredSize: new go.Size(30, 30), // Set the size of the button
                        textAlign: "center",
                        verticalAlignment: go.Spot.Center
                    })
                ),
                gogo("Button",  // '-' button
                    {
                        alignment: go.Spot.BottomRight,
                        click: function (e, obj) {  // click event handler


                            const nodeToRemove = obj.part;

                            const isRoot = nodeToRemove.findLinksInto().count === 0; // check if this node is root (no incoming links)

                            if (isRoot) {
                                alert('Cannot delete root node!');
                                return;
                            }

                            diagram.startTransaction();
                            diagram.remove(nodeToRemove);

                            diagram.commitTransaction("Remove Node");

                            // Update the JSON structure
                            const root = findTreeRoot(diagram);
                            updatedJSON = createJSONstructure(root);

                            updateJsonFile(updatedJSON);
                        }
                    },
                    gogo(go.TextBlock, "-", {
                        font: "bold 12pt Arial",
                        background: "#f44336", // Red background
                        desiredSize: new go.Size(30, 30), // Set the size of the button
                        textAlign: "center",
                        verticalAlignment: go.Spot.Center
                    })
                ),

                gogo("Button",  // 'edit' button
                    {
                        alignment: go.Spot.BottomCenter,
                        click: async function (e, obj) {  // click event handler
                            const nodeToEdit = obj.part;

                            // Pre-fill the modal with the existing values
                            nodeTitle = nodeToEdit.data.text;
                            nodeContent = nodeToEdit.data.content;
                            selectChoice = nodeToEdit.category;

                            // Show the modal
                            await showModal(nodeTitle, nodeContent, selectChoice);

                            // Save the edited data back to the node
                            diagram.startTransaction();
                            diagram.model.setDataProperty(nodeToEdit.data, "text", nodeTitle);
                            diagram.model.setDataProperty(nodeToEdit.data, "content", nodeContent);
                            diagram.model.setDataProperty(nodeToEdit.data, "category", selectChoice);
                            diagram.commitTransaction("Edit Node");

                            // Update the JSON structure
                            const root = findTreeRoot(diagram);
                            updatedJSON = createJSONstructure(root);

                            updateJsonFile(updatedJSON)
                        }
                    },
                    gogo(go.TextBlock, "edit", {
                        font: "bold 12pt Arial",
                        background: "#008CBA", // Blue background
                        desiredSize: new go.Size(50, 30), // Set the size of the button
                        textAlign: "center",
                        verticalAlignment: go.Spot.Center
                    })
                )
            );
        }

        diagram.nodeTemplateMap.add("quest", createNodeTemplate("#faedff"));
        diagram.nodeTemplateMap.add("choice", createNodeTemplate("#bbfcff"));
        diagram.nodeTemplateMap.add("result", createNodeTemplate("#dcfeda"));

        function createNodesAndLinks(data, parentKey) {
            const nodeKey = data.type + "-" + keyCounter++;
            const location = new go.Point(data.x || 0, data.y || 0);
            console.log(`Creating node with x: ${data.x}, y: ${data.y}`); // Log x and y to console
            const node = Object.assign({}, data);  // copy all properties from data

            // overwrite properties you want to specify
            node.key = data.key ? nodeKey : data.key;
            node.label = data.title;
            node.category = data.type;
            node.content = data.content;
            node.x = data.x ? data.x : new go.Point(0, 0);
            node.y = data.y ? data.y : new go.Point(0, 0);
            node.loc = location;
            node.type = data.type;
            node.text = data.title;

            diagram.model.addNodeData(node);
            diagram.commitTransaction("Add node");

            if (data.children && data.children.length > 0) {
                data.children.forEach((child) => {
                    const childKey = createNodesAndLinks(child);
                    diagram.startTransaction();
                    const link = {from: nodeKey, to: childKey};
                    diagram.model.addLinkData(link);
                    diagram.commitTransaction("Add link");
                });
            }

            return nodeKey;
        }

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: "get_tree",
            },
            success: function (data) {
                jsonData = JSON.parse(data);

                // Assign jsonData to updatedJSON here
                updatedJSON = jsonData;

                // Place the subsequent code here
                // Use jsonData as needed
                createNodesAndLinks(jsonData);
                // Layout the diagram
                diagram.layout = gogo(go.TreeLayout, {angle: 90});


                setTimeout(function () {
                    // Set positions after rendering
                    setNodePositions(diagram, jsonData);
                }, 0);

                function setNodePositions(diagram, jsonData) {
                    if (jsonData.key && jsonData.x !== undefined && jsonData.y !== undefined) {
                        const node = diagram.findNodeForKey(jsonData.key);
                        if (node) {
                            node.position = new go.Point(jsonData.x, jsonData.y);
                        }
                    }

                    if (jsonData.children) {
                        jsonData.children.forEach(child => setNodePositions(diagram, child));
                    }
                }


                // Initial diagram load completed
                diagram.addDiagramListener("InitialLayoutCompleted", function () {
                    diagram.zoomToFit();
                });
            },
            error: function (errorThrown) {
                console.log('error', errorThrown);
            }
        });


        function exportJson() {
            const blob = new Blob([JSON.stringify(updatedJSON, null, 2)], {type: 'application/json'});
            const url = URL.createObjectURL(blob);

            const a = document.createElement('a');
            a.href = url;
            a.download = 'tree.json';
            a.click();

            URL.revokeObjectURL(url);
        }

        $('#fileInput').on('change', function (event) {
            let file_data = $(this)[0].files[0];
            let form_data = new FormData();

            // file_data = JSON.stringify(file_data, null, 2)
            // console.log(file_data)

            form_data.append('file', file_data);
            form_data.append('action', 'upload_file');

            $.ajax({
                url: ajaxurl, // point to server-side PHP script
                type: 'POST',
                dataType: 'json',  // what to expect back from the PHP script
                cache: false,
                contentType: false,
                processData: false,
                data: form_data,
                success: function (response) {
                    // jsonData = JSON.parse(response);

                    // Assign jsonData to updatedJSON here
                    updatedJSON = response;

                    // Place the subsequent code here
                    // Use jsonData as needed
                    createNodesAndLinks(response);
                    // Layout the diagram
                    // diagram.layout = gogo(go.TreeLayout, {angle: 90});
                    //
                    // // Initial diagram load completed
                    // diagram.addDiagramListener("InitialLayoutCompleted", function () {
                    //     diagram.zoomToFit();
                    // });
                }
            });
        })

        $('#download-json').on('click', function (event) {
            exportJson();
        });


        function test() {
            console.log('test')
        }
       new RichTextEditor("#div_editor1");

    })(jQuery);
</script>


    <!--<div id="decisionTree">-->
    <!--    <div class="node" data-type="quest" style="background-color: #faedff;">-->
    <!--        <div id="editor"></div>-->
    <!--        <div class="actions">-->
    <!--            <select onchange="changeType(this)">-->
    <!--                <option value="quest" selected>Quest</option>-->
    <!--                <option value="choice">Choice</option>-->
    <!--                <option value="result">Result</option>-->
    <!--                <option value="math">Math</option>-->
    <!--            </select>-->
    <!--            <div class="buttons">-->
    <!--                <button onclick="addNode(this)">Add Child</button>-->
    <!--                <button onclick="deleteNode(this)">Delete Node</button>-->
    <!--            </div>-->
    <!--        </div>-->
    <!--        <div class="children"></div>-->
    <!--    </div>-->
    <!--</div>-->
    <!---->
    <!--<div class="treeOperationButtons">-->
    <!--    <button id="saveTree" class="action-button" onclick="generateTree()">Save Changes</button>-->
    <!--    <button class="action-button" onclick="exportJson()">Export JSON</button>-->
    <!--    <label for="fileInput" class="action-button file-upload-label">-->
    <!--        Import JSON-->
    <!--        <input type="file" id="fileInput" accept=".json" onchange="loadJson(event)" />-->
    <!--    </label>-->
    <!--</div>-->


<?php

//$builder = new Tree\Builder\NodeBuilder;
//
//$builder
//	->value('A')
//	->leaf('B')
//	->tree('C')
//		->tree('D')
//			->leaf('G')
//			->leaf('H')
//			->end()
//		->leaf('E')
//		->leaf('F')
//		->end();
//
//$nodeA = $builder->getNode();
//
//// make nodeA to array
//
//function convertNodeToArray(Tree\Node\Node $node): array
//{
//	$children = [];
//
//	foreach ($node->getChildren() as $childNode) {
//		$children[] = convertNodeToArray($childNode);
//	}
//
//	return [
//		'value' => $node->getValue(),
//		'children' => $children,
//	];
//}
//
//$array = convertNodeToArray($nodeA);
//
//
//print "<pre>";
//print_r($array);