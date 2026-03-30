<?php
/**
 * Reusable Table Component (Enhanced)
 * 
 * New Params:
 * @param string $pageParamName Name of page URL parameter (default: 'page')
 * @param string $perPageParamName Name of per_page URL parameter (default: 'per_page')
 * @param bool $showPerPage Show the rows per page input (default: false)
 */

$idField = $idField ?? 'id';
$currentSort = $_GET['sort'] ?? '';
$currentOrder = $_GET['order'] ?? '';

// Defaults
$pageParamName = $pageParamName ?? 'page';
$perPageParamName = $perPageParamName ?? 'per_page';
$showPerPage = $showPerPage ?? false;

// Helper to generate sort links (preserves other params)
$makeSortLink = function ($col, $label) use ($filtros, $currentSort, $currentOrder, $pageParamName) {
    if (empty($col)) return $label;

    $newOrder = ($currentSort === $col && $currentOrder === 'ASC') ? 'DESC' : 'ASC';
    $icon = ($currentSort === $col) ? ($currentOrder === 'ASC' ? ' ▲' : ' ▼') : ' <span style="opacity:0.3; font-size: 0.8em">⇅</span>';

    // Must reset page to 1 when sorting
    $params = array_merge($filtros ?? [], ['sort' => $col, 'order' => $newOrder, $pageParamName => 1]);
    return '<a href="?' . http_build_query($params) . '" style="color: inherit; text-decoration: none; display: flex; align-items: center; justify-content: space-between;">' . $label . $icon . '</a>';
};
?>

<?php if (!empty($showPerPage) && !empty($paginacion)): ?>
    <div style="display: flex; justify-content: flex-end; margin-bottom: 10px; align-items: center; gap: 10px;">
        <label style="font-size: 14px; color: #666;">Filas por pág:</label>
        <input type="number" 
               value="<?= $paginacion['per_page'] ?? 10 ?>" 
               min="1" max="500"
               style="width: 60px; padding: 4px; border: 1px solid #ccc; border-radius: 4px;"
               onchange="updatePerPageParam(this.value, '<?= $perPageParamName ?>', '<?= $pageParamName ?>')">
    </div>
    
    <script>
    if (typeof updatePerPageParam === 'undefined') {
        window.updatePerPageParam = function(val, ppName, pName) {
            val = parseInt(val);
            if (val < 1) val = 1;
            const urlParams = new URLSearchParams(window.location.search);
            urlParams.set(ppName, val);
            urlParams.set(pName, 1); // Reset page to 1
            window.location.search = urlParams.toString();
        }
    }
    </script>
<?php endif; ?>

<?php if (empty($rows)): ?>
    <div class="alert alert-info">
        No se encontraron registros.
    </div>
<?php else: ?>
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <?php if (!empty($modoLotes)): ?>
                        <th style="width: 50px;">
                            <input type="checkbox" id="<?= isset($checkboxId) ? $checkboxId : 'seleccionar-todos' ?>" 
                                   onclick="<?= isset($checkboxOnClick) ? $checkboxOnClick : 'toggleTodos(this)' ?>">
                        </th>
                    <?php endif; ?>

                    <?php foreach ($columns as $col): ?>
                        <th style="<?= isset($col['width']) ? "width: {$col['width']};" : '' ?>">
                            <?php if (!empty($col['sortable']) && !empty($col['field'])): ?>
                                <?= $makeSortLink($col['field'], $col['label']) ?>
                            <?php else: ?>
                                <?= $col['label'] ?>
                            <?php endif; ?>
                        </th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($rows as $row):
                    $rowClass = is_callable($rowClassCallback ?? null) ? $rowClassCallback($row) : '';
                    ?>
                    <tr class="<?= $rowClass ?>" data-id="<?= $row[$idField] ?? '' ?>">
                        <?php if (!empty($modoLotes)): ?>
                            <td>
                                <?php
                                if (isset($batchCheckboxFormatter) && is_callable($batchCheckboxFormatter)) {
                                    echo $batchCheckboxFormatter($row);
                                } else {
                                    echo '<input type="checkbox" class="doc-checkbox" value="' . ($row[$idField] ?? '') . '">';
                                }
                                ?>
                            </td>
                        <?php endif; ?>

                        <?php foreach ($columns as $col): ?>
                            <td>
                                <?php
                                if (isset($col['formatter']) && is_callable($col['formatter'])) {
                                    $qs = http_build_query($filtros ?? []); 
                                    $append = $qs ? '?' . $qs : '';
                                    echo $col['formatter']($row, $append);
                                } else {
                                    echo htmlspecialchars($row[$col['field']] ?? 'N/A');
                                }
                                ?>
                            </td>
                        <?php endforeach; ?>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <?php if (isset($paginacion) && ($paginacion['total_pages'] ?? 1) > 1): ?>
        <div class="pagination">
            <?php
            $current = $paginacion['page'];
            $total = $paginacion['total_pages'];
            $max_visible = 10;
            $start = max(1, $current - floor($max_visible / 2));
            $end = min($total, $start + $max_visible - 1);
            if ($end - $start + 1 < $max_visible) {
                $start = max(1, $end - $max_visible + 1);
            }
            
            // Build Base Params for Links
            $params = array_merge($filtros ?? [], ['modo_lotes' => !empty($modoLotes) ? '1' : null]);
            // Ensure we keep the per_page param if set
            if (isset($paginacion['per_page'])) {
                $params[$perPageParamName] = $paginacion['per_page'];
            }
            ?>

            <?php if ($current > 1): ?>
                <a href="?<?= http_build_query(array_merge($params, [$pageParamName => 1])) ?>" class="btn btn-secondary">⇤ Primero</a>
            <?php endif; ?>

            <?php if ($current > 1): ?>
                <a href="?<?= http_build_query(array_merge($params, [$pageParamName => $current - 1])) ?>" class="btn btn-warning">← Anterior</a>
            <?php else: ?>
                <button class="btn btn-secondary" disabled>← Anterior</button>
            <?php endif; ?>

            <div class="pagination-numbers">
                <?php for ($i = $start; $i <= $end; $i++): ?>
                    <a href="?<?= http_build_query(array_merge($params, [$pageParamName => $i])) ?>"
                        class="btn <?= $i == $current ? 'btn-primary active' : 'btn-light' ?> page-num">
                        <?= $i ?>
                    </a>
                <?php endfor; ?>
            </div>

            <?php if ($current < $total): ?>
                <a href="?<?= http_build_query(array_merge($params, [$pageParamName => $current + 1])) ?>" class="btn btn-warning">Siguiente →</a>
            <?php else: ?>
                <button class="btn btn-secondary" disabled>Siguiente →</button>
            <?php endif; ?>

            <?php if ($current < $total): ?>
                <a href="?<?= http_build_query(array_merge($params, [$pageParamName => $total])) ?>" class="btn btn-secondary">Último ⇥</a>
            <?php endif; ?>
        </div>
    <?php endif; ?>
<?php endif; ?>