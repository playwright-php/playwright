<?php

declare(strict_types=1);

/*
 * This file is part of the community-maintained Playwright PHP project.
 * It is not affiliated with or endorsed by Microsoft.
 *
 * (c) 2025-Present - Playwright PHP - https://github.com/playwright-php
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

require_once __DIR__.'/../../vendor/autoload.php';

use Playwright\Playwright;

$browser = Playwright::chromium();
$page = $browser->newPage();

// Navigate to a page with draggable elements
$page->setContent('
<!DOCTYPE html>
<html>
<head>
    <style>
        .container { padding: 20px; }
        .draggable {
            width: 100px;
            height: 100px;
            background: #4CAF50;
            color: white;
            text-align: center;
            line-height: 100px;
            cursor: move;
            margin: 10px;
            user-select: none;
        }
        .dropzone {
            width: 200px;
            height: 200px;
            border: 2px dashed #ccc;
            text-align: center;
            margin: 20px;
            background: #f9f9f9;
            position: relative;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: flex-start;
            padding: 10px;
            box-sizing: border-box;
        }
        .dropzone.dragover {
            background: #e8f5e8;
            border-color: #4CAF50;
        }
        .dropzone.has-items {
            background: #e8f4fd;
            border-color: #2196F3;
            border-style: solid;
        }
        .dropzone .label {
            font-weight: bold;
            margin-bottom: 10px;
            color: #666;
        }
        .dropped-item {
            background: #2196F3 !important;
            color: white !important;
            margin: 5px 0;
            transform: scale(0.9);
            border-radius: 5px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Drag and Drop Demo</h1>
        
        <div class="draggable" id="item1" draggable="true">Item 1</div>
        <div class="draggable" id="item2" draggable="true">Item 2</div>
        <div class="draggable" id="item3" draggable="true">Item 3</div>
        
        <div class="dropzone" id="dropzone1">
            <div class="label">Drop Zone 1</div>
        </div>
        <div class="dropzone" id="dropzone2">
            <div class="label">Drop Zone 2</div>
        </div>
    </div>
    
    <script>
        // Add drag and drop event handlers
        document.querySelectorAll(".draggable").forEach(item => {
            item.addEventListener("dragstart", e => {
                e.dataTransfer.setData("text/plain", e.target.id);
            });
        });
        
        document.querySelectorAll(".dropzone").forEach(zone => {
            zone.addEventListener("dragover", e => {
                e.preventDefault();
                zone.classList.add("dragover");
            });
            
            zone.addEventListener("dragleave", () => {
                zone.classList.remove("dragover");
            });
            
            zone.addEventListener("drop", e => {
                e.preventDefault();
                const itemId = e.dataTransfer.getData("text/plain");
                const item = document.getElementById(itemId);
                
                // Add visual feedback
                item.classList.add("dropped-item");
                
                // Append to drop zone (keeping the label)
                zone.appendChild(item);
                zone.classList.remove("dragover");
                zone.classList.add("has-items");
            });
        });
    </script>
</body>
</html>
');

// Take initial screenshot
$page->screenshot('/tmp/drag-drop-initial.png');
echo "Initial screenshot saved: /tmp/drag-drop-initial.png\n";

// Example 1: Basic drag and drop
echo "Performing basic drag and drop...\n";
$draggableItem = $page->locator('#item1');
$dropZone = $page->locator('#dropzone1');

$draggableItem->dragTo($dropZone);
$page->screenshot('/tmp/drag-drop-after-item1.png');
echo "Screenshot after dragging item1: /tmp/drag-drop-after-item1.png\n";

// Example 2: Drag with specific positions
echo "Performing drag with specific source and target positions...\n";
$draggableItem2 = $page->locator('#item2');
$dropZone2 = $page->locator('#dropzone2');

$draggableItem2->dragTo($dropZone2, [
    'sourcePosition' => ['x' => 50, 'y' => 50], // Center of source element
    'targetPosition' => ['x' => 100, 'y' => 100], // Center of target element
]);
$page->screenshot('/tmp/drag-drop-after-item2.png');
echo "Screenshot after dragging item2 with positions: /tmp/drag-drop-after-item2.png\n";

// Example 3: Force drag (bypass actionability checks)
echo "Performing forced drag and drop...\n";
$draggableItem3 = $page->locator('#item3');

$draggableItem3->dragTo($dropZone, [
    'force' => true,
    'timeout' => 5000,
]);
$page->screenshot('/tmp/drag-drop-after-item3.png');
echo "Screenshot after forced drag of item3: /tmp/drag-drop-after-item3.png\n";

// Example 4: Verify drop results
echo "Verifying drop results...\n";
$dropZone1Items = $page->locator('#dropzone1 .draggable');
$dropZone2Items = $page->locator('#dropzone2 .draggable');

$zone1Count = $dropZone1Items->count();
$zone2Count = $dropZone2Items->count();

echo "Drop Zone 1 contains $zone1Count items\n";
echo "Drop Zone 2 contains $zone2Count items\n";

// Take final screenshot
$page->screenshot('/tmp/drag-drop-final.png');
echo "Final screenshot saved: /tmp/drag-drop-final.png\n";

// Cleanup
$page->close();
$browser->close();

echo "\nDrag and drop examples completed successfully!\n";
echo "Screenshots saved in /tmp/ directory:\n";
echo "- drag-drop-initial.png\n";
echo "- drag-drop-after-item1.png\n";
echo "- drag-drop-after-item2.png\n";
echo "- drag-drop-after-item3.png\n";
echo "- drag-drop-final.png\n";
