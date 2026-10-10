/**
 * Softaware Admin Activity Log
 *
 * Filters component without the collapsible panel: the fields live in the grid's filter row (see listing.js),
 * the toolbar shows Search and Reset Filter buttons and the chips of the applied filters.
 */
define([
    'Magento_Ui/js/grid/filters/filters'
], function (Filters) {
    'use strict';

    return Filters.extend({
        defaults: {
            template: 'Softaware_ActivityLog/grid/filters',
            stickyTmpl: 'Softaware_ActivityLog/grid/sticky-filters'
        },

        /**
         * Clears every filter field, applied or only typed, and reloads the grid.
         *
         * @returns {Filters} Chainable.
         */
        reset: function () {
            this.elems.each(function (filter) {
                filter.clear();
            });

            return this.apply();
        },

        /**
         * Sticky header button: back to the top of the grid, cursor in the first filter field.
         */
        scrollToFilters: function () {
            var field = document.querySelector('[data-role="filter-row"] input, [data-role="filter-row"] select');

            window.scrollTo(0, 0);

            if (field) {
                field.focus();
            }
        }
    });
});
