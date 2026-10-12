@props([
    'title' => 'Konfirmasi',
    'message' => 'Apakah Anda yakin ingin melanjutkan?',
    'confirmText' => 'Ya, Lanjutkan',
    'cancelText' => 'Batal',
    'type' => 'danger'
])

{{-- Confirm Dialog Component --}}
<div
    x-data="{
        open: false,
        title: '{{ $title }}',
        message: '{{ $message }}',
        confirmText: '{{ $confirmText }}',
        type: '{{ $type }}',
        formAction: null,
        method: 'DELETE',

        show(options = {}) {
            this.title = options.title || '{{ $title }}';
            this.message = options.message || '{{ $message }}';
            this.confirmText = options.confirmText || '{{ $confirmText }}';
            this.type = options.type || '{{ $type }}';
            this.formAction = options.formAction || null;
            this.method = options.method || 'DELETE';
            this.open = true;
        },

        close() {
            this.open = false;
        },

        confirm() {
            if (this.formAction) {
                this.$refs.confirmForm.action = this.formAction;
                this.$refs.methodInput.value = this.method;
                this.$refs.confirmForm.submit();
            } else {
                this.close();
            }
        }
    }"
    x-on:confirm-dialog.window="show($event.detail)"
    x-on:keydown.escape.window="if(open) close()"
>
    {{-- Modal Wrapper --}}
    <div
        x-show="open"
        x-cloak
        style="position: fixed; top: 0; left: 0; right: 0; bottom: 0; z-index: 99999;"
    >
        {{-- Backdrop --}}
        <div
            x-show="open"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            @click="close()"
            style="position: absolute; top: 0; left: 0; right: 0; bottom: 0; background-color: rgba(0, 0, 0, 0.5); backdrop-filter: blur(4px);"
        ></div>

        {{-- Dialog Centering Container --}}
        <div style="position: absolute; top: 0; left: 0; right: 0; bottom: 0; display: flex; align-items: center; justify-content: center; padding: 1rem; pointer-events: none;">
            {{-- Dialog Panel --}}
            <div
                x-show="open"
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0 transform scale-95"
                x-transition:enter-end="opacity-100 transform scale-100"
                x-transition:leave="transition ease-in duration-150"
                x-transition:leave-start="opacity-100 transform scale-100"
                x-transition:leave-end="opacity-0 transform scale-95"
                @click.stop
                style="pointer-events: auto; width: 100%; max-width: 28rem; background-color: white; border-radius: 1rem; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25); overflow: hidden;"
            >
                {{-- Content --}}
                <div style="padding: 1.5rem;">
                    <div style="display: flex; align-items: flex-start; gap: 1rem;">
                        {{-- Icon --}}
                        <div
                            style="flex-shrink: 0; width: 3rem; height: 3rem; border-radius: 9999px; display: flex; align-items: center; justify-content: center;"
                            :style="type === 'danger' ? 'background-color: #fee2e2;' : (type === 'warning' ? 'background-color: #fef3c7;' : 'background-color: #dbeafe;')"
                        >
                            <svg
                                style="width: 1.5rem; height: 1.5rem;"
                                :style="type === 'danger' ? 'color: #dc2626;' : (type === 'warning' ? 'color: #d97706;' : 'color: #2563eb;')"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke-width="1.5"
                                stroke="currentColor"
                            >
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                            </svg>
                        </div>

                        {{-- Text --}}
                        <div style="flex: 1; min-width: 0;">
                            <h3 style="font-size: 1.125rem; font-weight: 600; color: #0f172a; margin: 0; line-height: 1.5;" x-text="title"></h3>
                            <p style="margin: 0.5rem 0 0 0; font-size: 0.875rem; color: #64748b; line-height: 1.5;" x-text="message"></p>
                        </div>
                    </div>
                </div>

                {{-- Actions --}}
                <div style="background-color: #f8fafc; padding: 1rem 1.5rem; display: flex; justify-content: flex-end; gap: 0.75rem;">
                    <button
                        type="button"
                        @click="close()"
                        class="btn btn-ghost"
                    >
                        {{ $cancelText }}
                    </button>
                    <form x-ref="confirmForm" method="POST" style="display: inline; margin: 0;">
                        @csrf
                        <input type="hidden" name="_method" x-ref="methodInput" :value="method">
                        <button
                            type="button"
                            @click="confirm()"
                            class="btn"
                            :class="type === 'danger' ? 'btn-danger' : (type === 'warning' ? 'btn-warning' : 'btn-primary')"
                            x-text="confirmText"
                        ></button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
