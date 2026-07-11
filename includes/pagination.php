<?php
/**
 * Pagination Helper Functions
 * 
 * @param int $currentPage Current page number (1-based)
 * @param int $totalItems Total number of items
 * @param int $perPage Items per page
 * @param string $baseUrl Base URL for pagination links
 * @param string $paramName URL parameter name for page number
 * @return array Returns array with pagination data
 */
function paginate(int $currentPage, int $totalItems, int $perPage, string $baseUrl = '', string $paramName = 'page'): array {
    $totalPages = max(1, ceil($totalItems / $perPage));
    $currentPage = max(1, min($currentPage, $totalPages));
    
    $offset = ($currentPage - 1) * $perPage;
    
    return [
        'current_page' => $currentPage,
        'total_pages' => $totalPages,
        'total_items' => $totalItems,
        'per_page' => $perPage,
        'offset' => $offset,
        'has_prev' => $currentPage > 1,
        'has_next' => $currentPage < $totalPages,
        'prev_page' => $currentPage - 1,
        'next_page' => $currentPage + 1,
        'base_url' => $baseUrl,
        'param_name' => $paramName,
    ];
}

/**
 * Generate pagination URL with page number
 */
function getPaginationUrl(array $pagination, int $page): string {
    $url = $pagination['base_url'];
    $param = $pagination['param_name'];
    
    if (strpos($url, '?') !== false) {
        return $url . '&' . $param . '=' . $page;
    }
    return $url . '?' . $param . '=' . $page;
}

/**
 * Get page numbers to display with ellipsis for large page counts
 */
function getPageNumbers(array $pagination): array {
    $current = $pagination['current_page'];
    $total = $pagination['total_pages'];
    $pages = [];
    
    if ($total <= 7) {
        for ($i = 1; $i <= $total; $i++) {
            $pages[] = $i;
        }
    } else {
        $pages[] = 1;
        
        if ($current <= 4) {
            for ($i = 2; $i <= 5; $i++) {
                $pages[] = $i;
            }
            $pages[] = '...';
            $pages[] = $total;
        } elseif ($current >= $total - 3) {
            $pages[] = '...';
            for ($i = $total - 4; $i <= $total; $i++) {
                $pages[] = $i;
            }
        } else {
            $pages[] = '...';
            for ($i = $current - 1; $i <= $current + 1; $i++) {
                $pages[] = $i;
            }
            $pages[] = '...';
            $pages[] = $total;
        }
    }
    
    return $pages;
}

/**
 * Render full pagination with page numbers (always shows, disabled if single page)
 */
function renderPagination(array $pagination): string {
    $pages = getPageNumbers($pagination);
    $html = '<nav aria-label="Page navigation example" class="mt-6">
        <ul class="flex -space-x-px text-sm">
            <li>
                <a href="' . ($pagination['has_prev'] ? getPaginationUrl($pagination, $pagination['prev_page']) : '#') . '" 
                   class="flex items-center justify-center text-body bg-neutral-secondary-medium box-border border border-default-medium hover:bg-neutral-tertiary-medium hover:text-heading font-medium rounded-s-base text-sm px-3 h-10 focus:outline-none ' . (!$pagination['has_prev'] ? 'opacity-50 pointer-events-none' : '') . '">
                    Previous
                </a>
            </li>';
    
    foreach ($pages as $page) {
        if ($page === '...') {
            $html .= '<li><span class="flex items-center justify-center text-body bg-neutral-secondary-medium border border-default-medium text-sm w-10 h-10">...</span></li>';
        } else {
            $isActive = $page == $pagination['current_page'];
            $html .= '<li>
                <a href="' . getPaginationUrl($pagination, $page) . '" 
                   class="flex items-center justify-center ' . ($isActive ? 'text-fg-brand bg-neutral-tertiary-medium' : 'text-body bg-neutral-secondary-medium') . ' box-border border border-default-medium hover:bg-neutral-tertiary-medium hover:text-heading font-medium text-sm w-10 h-10 focus:outline-none">
                    ' . $page . '
                </a>
            </li>';
        }
    }
    
    $html .= '<li>
                <a href="' . ($pagination['has_next'] ? getPaginationUrl($pagination, $pagination['next_page']) : '#') . '" 
                   class="flex items-center justify-center text-body bg-neutral-secondary-medium box-border border border-default-medium hover:bg-neutral-tertiary-medium hover:text-heading font-medium rounded-e-base text-sm px-3 h-10 focus:outline-none ' . (!$pagination['has_next'] ? 'opacity-50 pointer-events-none' : '') . '">
                    Next
                </a>
            </li>
        </ul>
    </nav>';
    
    return $html;
}

/**
 * Render compact pagination with only Previous/Next buttons (always shows, disabled if single page)
 */
function renderPaginationCompact(array $pagination): string {
    $start = $pagination['offset'] + 1;
    $end = min($pagination['offset'] + $pagination['per_page'], $pagination['total_items']);
    
    return '<div class="flex flex-col items-center mt-6 pt-4 border-t border-border-subtle">
    <span class="text-sm text-body">
        Showing <span class="font-semibold text-heading">' . $start . '</span> to <span class="font-semibold text-heading">' . $end . '</span> of <span class="font-semibold text-heading">' . number_format($pagination['total_items']) . '</span> Entries
    </span>
    <div class="inline-flex mt-4 -space-x-px">
        <a href="' . ($pagination['has_prev'] ? getPaginationUrl($pagination, $pagination['prev_page']) : '#') . '" 
           class="inline-flex items-center text-body bg-neutral-secondary-medium border border-default-medium hover:bg-neutral-tertiary-medium hover:text-heading shadow-xs font-medium leading-5 rounded-s-base text-sm px-4 py-2.5 focus:outline-none ' . (!$pagination['has_prev'] ? 'opacity-50 pointer-events-none' : '') . '">
            <svg class="w-4 h-4 me-1.5 -ms-0.5 rtl:rotate-180" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24"><path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M5 12l4-4m-4 4 4 4"/></svg>
            Previous
        </a>
        <a href="' . ($pagination['has_next'] ? getPaginationUrl($pagination, $pagination['next_page']) : '#') . '" 
           class="inline-flex items-center text-body bg-neutral-secondary-medium border border-default-medium hover:bg-neutral-tertiary-medium hover:text-heading shadow-xs font-medium leading-5 rounded-e-base text-sm px-4 py-2.5 focus:outline-none ' . (!$pagination['has_next'] ? 'opacity-50 pointer-events-none' : '') . '">
            Next
            <svg class="w-4 h-4 ms-1.5 -me-0.5 rtl:rotate-180" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24"><path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 12H5m14 0-4 4m4-4-4-4"/></svg>
        </a>
    </div>
</div>';
}

/**
 * Render full pagination with entries info (always shows, disabled if single page)
 */
function renderPaginationWithInfo(array $pagination): string {
    $start = $pagination['offset'] + 1;
    $end = min($pagination['offset'] + $pagination['per_page'], $pagination['total_items']);
    
    $pages = getPageNumbers($pagination);
    $html = '<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mt-6 pt-4 border-t border-border-subtle">
        <span class="text-sm text-body">
            Showing <span class="font-semibold text-heading">' . $start . '</span> to <span class="font-semibold text-heading">' . $end . '</span> of <span class="font-semibold text-heading">' . number_format($pagination['total_items']) . '</span> Entries
        </span>
        <nav aria-label="Page navigation">
            <ul class="flex -space-x-px text-sm">
                <li>
                    <a href="' . ($pagination['has_prev'] ? getPaginationUrl($pagination, $pagination['prev_page']) : '#') . '" 
                       class="flex items-center justify-center text-body bg-neutral-secondary-medium box-border border border-default-medium hover:bg-neutral-tertiary-medium hover:text-heading font-medium rounded-s-base text-sm px-3 h-10 focus:outline-none ' . (!$pagination['has_prev'] ? 'opacity-50 pointer-events-none' : '') . '">
                        Previous
                    </a>
                </li>';
    
    foreach ($pages as $page) {
        if ($page === '...') {
            $html .= '<li><span class="flex items-center justify-center text-body bg-neutral-secondary-medium border border-default-medium text-sm w-10 h-10">...</span></li>';
        } else {
            $isActive = $page == $pagination['current_page'];
            $html .= '<li>
                <a href="' . getPaginationUrl($pagination, $page) . '" 
                   class="flex items-center justify-center ' . ($isActive ? 'text-fg-brand bg-neutral-tertiary-medium' : 'text-body bg-neutral-secondary-medium') . ' box-border border border-default-medium hover:bg-neutral-tertiary-medium hover:text-heading font-medium text-sm w-10 h-10 focus:outline-none">
                    ' . $page . '
                </a>
            </li>';
        }
    }
    
    $html .= '<li>
                <a href="' . ($pagination['has_next'] ? getPaginationUrl($pagination, $pagination['next_page']) : '#') . '" 
                   class="flex items-center justify-center text-body bg-neutral-secondary-medium box-border border border-default-medium hover:bg-neutral-tertiary-medium hover:text-heading font-medium rounded-e-base text-sm px-3 h-10 focus:outline-none ' . (!$pagination['has_next'] ? 'opacity-50 pointer-events-none' : '') . '">
                    Next
                </a>
            </li>
        </ul>
    </nav>
</div>';
    
    return $html;
}
