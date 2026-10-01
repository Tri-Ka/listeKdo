<?php
/*
 * Champs « question secrète » (inscription, profil, invitation après connexion).
 * $current : question actuelle (ou '') ; $required : réponse obligatoire.
 */
$current = isset($current) ? (string) $current : '';
$required = !empty($required);
$isListed = false;
foreach (secret_questions() as $questions) {
    if (in_array($current, $questions)) {
        $isListed = true;
    }
}
?>
<div class="secret-fields" data-secret-fields>
    <label class="field">
        <span>Question secrète<?php echo $required ? ' *' : ''; ?> <small>(pour récupérer votre mot de passe si vous l'oubliez)</small></span>
        <select name="secret_question" data-secret-select<?php echo $required ? ' required' : ''; ?>>
            <option value="">Choisir une question…</option>
            <?php foreach (secret_questions() as $group => $questions) : ?>
                <optgroup label="<?php echo e($group); ?>">
                    <?php foreach ($questions as $question) : ?>
                        <option value="<?php echo e($question); ?>"<?php echo $question === $current ? ' selected' : ''; ?>><?php echo e($question); ?></option>
                    <?php endforeach; ?>
                </optgroup>
            <?php endforeach; ?>
            <option value="__custom"<?php echo '' !== $current && !$isListed ? ' selected' : ''; ?>>✏️ Écrire ma propre question…</option>
        </select>
    </label>
    <label class="field" data-secret-custom<?php echo '' !== $current && !$isListed ? '' : ' hidden'; ?>>
        <span>Ma question</span>
        <input type="text" name="secret_question_custom" maxlength="255" value="<?php echo '' !== $current && !$isListed ? e($current) : ''; ?>" placeholder="Ex. : Comment s'appelait le chien de mamie ?">
    </label>
    <label class="field">
        <span>Réponse<?php echo $required ? ' *' : ''; ?> <small>(majuscules et accents ne comptent pas)</small></span>
        <input type="text" name="secret_answer" maxlength="255" autocomplete="off"<?php echo $required ? ' required' : ''; ?>
            placeholder="<?php echo '' !== $current ? 'Laisser vide pour garder la réponse actuelle' : ''; ?>">
    </label>
</div>
