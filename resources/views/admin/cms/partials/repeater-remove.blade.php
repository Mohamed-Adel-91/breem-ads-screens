{{-- Removes a repeater row from the form. The row is only deleted in the
     database once the surrounding page form is saved. --}}
<button type="button"
        class="btn btn-sm btn-outline-danger"
        data-repeater-remove
        data-repeater-confirm="{{ __('admin.cms.ui.remove_item_confirm') }}"
        aria-label="{{ __('admin.cms.ui.remove_item') }}">
    <i class="fe fe-trash-2" aria-hidden="true"></i>
    <span>{{ __('admin.cms.ui.remove_item') }}</span>
</button>
