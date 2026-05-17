<?php
/**
 * Laleh — Stylist chatbot (script-based, no external AI API)
 *
 * POST params:
 *   message  (string) — user message
 *   clear    (1)      — reset conversation
 *
 * Response JSON:
 *   { ok: true, reply: "...", history_len: N }
 *   { ok: false, msg: "..." }
 */

session_start();
require_once __DIR__ . '/db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_out(['ok' => false, 'msg' => 'POST only']);
}

if (isset($_POST['clear'])) {
    $_SESSION['chat_history'] = [];
    unset($_SESSION['chat_topic']);
    json_out(['ok' => true, 'reply' => 'Cleared. How can I style you today? ✨']);
}

$message = trim($_POST['message'] ?? '');
if ($message === '') {
    json_out(['ok' => false, 'msg' => 'Empty message']);
}
if (mb_strlen($message) > 500) {
    json_out(['ok' => false, 'msg' => 'Message too long (max 500 chars)']);
}

if (!isset($_SESSION['chat_history']) || !is_array($_SESSION['chat_history'])) {
    $_SESSION['chat_history'] = [];
}

$_SESSION['chat_history'][] = ['role' => 'user', 'content' => $message];
$_SESSION['chat_history'] = array_slice($_SESSION['chat_history'], -10);

$reply = stylistReply($message);

$_SESSION['chat_history'][] = ['role' => 'assistant', 'content' => $reply];
json_out([
    'ok'          => true,
    'reply'       => $reply,
    'history_len' => count($_SESSION['chat_history']),
]);

/**
 * Rule-based stylist replies — patterns checked in order (first match wins).
 */
function stylistReply(string $msg): string
{
    $t = mb_strtolower($msg);

    // Short affirmations / follow-ups tied to last topic
    if (preg_match('/^(yes|yeah|yep|sure|ok|okay|please|go on|tell me more)\b/i', $t)) {
        $topic = $_SESSION['chat_topic'] ?? '';
        return match ($topic) {
            'outfit'   => "Start with one hero piece — a dress or structured blazer — then add one texture contrast (silk + knit, leather + cotton). Open Diva Studio to lay it out visually. 👗",
            'color'    => "Try the 60-30-10 rule: 60% neutral (ivory, beige, black), 30% supporting tone, 10% accent (gold jewelry or a bold lip). 🎨",
            'weather'  => "Layer thin-to-thick: base tee, light knit, coat. Remove layers as the day warms. The Planner syncs this with live weather. 🧥",
            'occasion' => "For smart-casual: tailored trousers, silk blouse, minimal heel. For formal: one statement piece, keep accessories refined. What's the dress code?",
            default    => "Tell me what you're dressing for — work, dinner, travel, or a special event — and I'll point you to the right Laleh tools. ✨",
        };
    }

    if (preg_match('/\b(help|what can you do|commands|features)\b/', $t)) {
        $_SESSION['chat_topic'] = 'help';
        return "I can guide you through Closet uploads, Diva Studio styling, Planner + weather looks, color pairing, and occasion dressing. Try: \"how do I add items\" or \"outfit for a wedding\". ✨";
    }

    if (preg_match('/\b(hi|hello|hey|good morning|good evening|bonjour|salut)\b/', $t)) {
        return "Hello, darling — welcome to your Laleh atelier. What are we styling today? ✨";
    }

    if (preg_match('/\b(bye|goodbye|see you|later|good night)\b/', $t)) {
        return "Until next time — stay impeccably dressed. ✨";
    }

    if (preg_match('/\b(diva|studio|mannequin|drag)\b/', $t)) {
        return "Diva Studio is your visual atelier: drag pieces from your closet onto the mannequin, save looks, and refine silhouettes before you wear them. 👗";
    }

    if (preg_match('/\b(dashboard|home|overview)\b/', $t)) {
        return "Your Dashboard shows AI outfit picks, recent looks, and quick links — it's your daily style command centre. ✨";
    }

    if (preg_match('/\b(weather|rain|cold|hot|warm|freezing|sunny|windy|snow|temperature)\b/', $t)) {
        $_SESSION['chat_topic'] = 'weather';
        return "Open the Planner — it reads live weather and suggests layers for your city. On chilly days: coat + knit + sleek boot. ☂️";
    }

    if (preg_match('/\b(outfit|suggest|recommend|wear|style|look|combination|what should i wear)\b/', $t)) {
        $_SESSION['chat_topic'] = 'outfit';
        return "Open Diva Studio to compose a look, or use ✦ Random Look in the Planner for an instant pick from your closet. 👗";
    }

    if (preg_match('/\b(color|colour|match|pair|palette|tone|coordinate)\b/', $t)) {
        $_SESSION['chat_topic'] = 'color';
        return "Ivory pairs beautifully with champagne, lilac, or soft gold — a classic Laleh palette. Anchor with one neutral, then add one accent. 🎨";
    }

    if (preg_match('/\b(upload|add|photo|picture|item|piece|closet|wardrobe)\b/', $t)) {
        return "Go to Closet → '+ Add Piece', upload a photo, and tag category & color — your digital archive builds itself. 📸";
    }

    if (preg_match('/\b(plan|calendar|schedule|date|event|tomorrow|week|monday|friday)\b/', $t)) {
        return "The Planner lets you assign outfits to calendar dates and adapts suggestions to live weather. 📅";
    }

    if (preg_match('/\b(shoe|heels|boot|sneaker|footwear)\b/', $t)) {
        return "Match shoe formality to the hemline: midi dress → heel or ankle boot; wide-leg → platform or pointed toe; casual set → clean white sneaker. 👠";
    }

    if (preg_match('/\b(bag|handbag|accessory|jewelry|scarf|belt)\b/', $t)) {
        return "One statement accessory is enough — let a sculptural bag or gold earrings carry the look while the outfit stays clean. 👜";
    }

    if (preg_match('/\b(trend|season|vogue|fashion|2025|2026)\b/', $t)) {
        return "This season favours quiet luxury — structured neutrals, clean silhouettes, and one refined statement piece. 👑";
    }

    if (preg_match('/\b(wedding|formal|gala|black tie)\b/', $t)) {
        $_SESSION['chat_topic'] = 'occasion';
        return "For formal events: one hero piece (gown or tailored suit), minimal jewelry, and a clutch that matches your metal tones. What's the venue — indoor or garden?";
    }

    if (preg_match('/\b(work|office|interview|business)\b/', $t)) {
        $_SESSION['chat_topic'] = 'occasion';
        return "Office polish: structured blazer, neutral base, one texture (silk, fine knit). Keep hemlines and necklines clean — confidence reads as luxury. 💼";
    }

    if (preg_match('/\b(casual|weekend|brunch|coffee)\b/', $t)) {
        $_SESSION['chat_topic'] = 'occasion';
        return "Elevated casual: quality denim or wide-leg trouser, fitted knit or crisp shirt, one elevated shoe. Add sunglasses as your finishing piece. ☕";
    }

    if (preg_match('/\b(party|dinner|night out|club|date)\b/', $t)) {
        $_SESSION['chat_topic'] = 'occasion';
        return "Evening looks love contrast — matte + shine, or fitted + fluid. Pick one focal point (shoulders, legs, or décolletage) and keep the rest understated. ✨";
    }

    if (preg_match('/\b(capsule|minimal|basics|essentials)\b/', $t)) {
        return "Build a capsule around 5 neutrals, 3 tops, 2 bottoms, 1 dress, 1 outer layer, and 2 shoes that all interchange. Quality over quantity. 🧥";
    }

    if (preg_match('/\b(thanks|thank you|merci|appreciate)\b/', $t)) {
        return "Always a pleasure. Stay finessed. ✨";
    }

    if (preg_match('/\b(great|amazing|love|best|perfect|good|awesome)\b/', $t)) {
        return "You have impeccable taste — that's why you're here. Anything else I can help you compose? 👗";
    }

    // Off-topic redirect
    if (preg_match('/\b(code|python|php|homework|politics|medical|doctor|crypto|stock)\b/', $t)) {
        return "I'm your fashion concierge — let's keep it to styling, your closet, and the Laleh studio. What look are you building? ✨";
    }

    return "I'm Laleh — your styling guide. Ask about outfits, colors, weather dressing, Closet uploads, Diva Studio, or the Planner. ✨";
}
