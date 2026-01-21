<?php if ($sessionUser && isset($currentUser['id']) && $sessionUser['id'] === $currentUser['id']): ?>
    <div class="text-center marged-top">
        <select class="" onchange="this.options[this.selectedIndex].value && (window.location = this.options[this.selectedIndex].value);">
            <option>-- Sélectionner un thème --</option>
            <?php foreach ($themes as $key => $theme): ?>
                <option value="actions/changeTheme.php?theme=<?php echo $key; ?>"><?php echo $theme; ?></option>
            <?php endforeach; ?>
        </select>
    </div>
<?php endif; ?>
