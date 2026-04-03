@php
    $promptUser = 'admin';
    $promptHost = parse_url(config('app.url', ''), PHP_URL_HOST) ?: 'localhost';
    $authMode = config('artisan-bar.auth_mode', 'password');
    $hasPasswordAuth = !empty(config('artisan-bar.password_hash')) ? '1' : '0';
    $shellMode = config('artisan-bar.shell_mode', 'disabled');
    $commands = json_encode(config('artisan-bar.commands', []), JSON_THROW_ON_ERROR);
    $shellAliases = json_encode(config('artisan-bar.shell_aliases', []), JSON_THROW_ON_ERROR);

    // Derive actual auth state from server
    $authResult = \Neluttu\ArtisanBar\ArtisanBarAuth::authorize(request());
    $isAuthenticated = $authResult->allowed ? '1' : '0';

    // Try to get current user name for prompt
    if (in_array($authMode, ['app-auth', 'either']) && auth()->check()) {
        $user = auth()->user();
        $promptUser = strtolower($user->name ?? ($user->email ?? 'admin'));
    }
@endphp

<link rel="stylesheet" href="{{ asset('vendor/artisan-bar/artisan-bar.css') }}">

<div id="artisan-bar"
     data-login-url="{{ route('artisan-bar.login') }}"
     data-logout-url="{{ route('artisan-bar.logout') }}"
     data-run-url="{{ route('artisan-bar.run') }}"
     data-csrf-token="{{ csrf_token() }}"
     data-auth-mode="{{ $authMode }}"
     data-has-password-auth="{{ $hasPasswordAuth }}"
     data-authenticated="{{ $isAuthenticated }}"
     data-prompt-user="{{ $promptUser }}"
     data-prompt-host="{{ $promptHost }}"
     data-commands="{{ $commands }}"
     data-shell-mode="{{ $shellMode }}"
     data-shell-aliases="{{ $shellAliases }}"
>
    {{-- Minimized tab --}}
    <div class="artisan-bar-tab">
        <svg fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m6.75 7.5 3 2.25-3 2.25m4.5 0h3m-9 8.25h13.5A2.25 2.25 0 0 0 21 18V6a2.25 2.25 0 0 0-2.25-2.25H5.25A2.25 2.25 0 0 0 3 6v12a2.25 2.25 0 0 0 2.25 2.25Z" /></svg>
        Artisan
    </div>

    {{-- Main panel --}}
    <div class="artisan-bar-main" style="display:none;">

        {{-- Output --}}
        <div class="artisan-bar-output-wrap" style="display:none;">
            <div class="artisan-bar-resize"></div>
            <div class="artisan-bar-output" style="height:224px;"></div>
        </div>

        {{-- Suggestions --}}
        <div class="artisan-bar-suggestions" style="display:none;"></div>

        {{-- Command bar --}}
        <div class="artisan-bar-bar">
            <div class="artisan-bar-input-row">
                {{-- Password input (shown when not authenticated) --}}
                <div class="artisan-bar-password-row" style="display:none;">
                    <span class="artisan-bar-prompt-sep">Password:</span>
                    <input type="password" class="artisan-bar-password-input" placeholder="Enter password..." autocomplete="off" spellcheck="false">
                </div>

                {{-- Command input (shown when authenticated) --}}
                <div class="artisan-bar-cmd-row" style="display:none;">
                    <span>
                        @if(config('artisan-bar.prompt.show_hostname', true))
                            <span class="artisan-bar-prompt-user">{!! e($promptUser . '@' . $promptHost) !!}</span><span class="artisan-bar-prompt-sep">$</span>
                        @else
                            <span class="artisan-bar-prompt-user">{!! e($promptUser) !!}</span><span class="artisan-bar-prompt-sep">$</span>
                        @endif
                    </span>
                    <input type="text" class="artisan-bar-input" placeholder="/help" autocomplete="off" spellcheck="false">
                </div>

                {{-- Shared controls --}}
                <div class="artisan-bar-spinner" style="display:none;"></div>
                <button class="artisan-bar-btn artisan-bar-btn-clear" style="display:none;">CLEAR</button>
                <button class="artisan-bar-btn artisan-bar-btn-minimize">
                    <svg fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 13.5 12 21m0 0-7.5-7.5M12 21V3" /></svg>
                </button>
            </div>
        </div>
    </div>
</div>

<script src="{{ asset('vendor/artisan-bar/artisan-bar.js') }}"></script>
