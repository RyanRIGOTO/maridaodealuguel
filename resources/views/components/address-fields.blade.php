<div
    x-data="{
        cep: @js(old('cep')),
        buscandoCep: false,
        erroCep: '',
        formatarCep() {
            const numeros = (this.cep || '').replace(/\D/g, '').slice(0, 8);
            this.cep = numeros.length > 5 ? numeros.slice(0, 5) + '-' + numeros.slice(5) : numeros;
        },
        async buscarCep() {
            const cep = (this.cep || '').replace(/\D/g, '');

            if (!cep) {
                this.erroCep = '';
                return;
            }
            if (cep.length !== 8) {
                this.erroCep = 'Informe os 8 dígitos do CEP.';
                return;
            }

            this.buscandoCep = true;
            this.erroCep = '';

            try {
                const resposta = await fetch(`https://viacep.com.br/ws/${cep}/json/`);
                const endereco = await resposta.json();

                if (!resposta.ok || endereco.erro) {
                    throw new Error('CEP não encontrado.');
                }

                this.$refs.logradouro.value = endereco.logradouro || '';
                this.$refs.bairro.value = endereco.bairro || '';
                this.$refs.cidade.value = endereco.localidade || '';
                this.$refs.uf.value = endereco.uf || '';
                this.$refs.numero.focus();
            } catch (error) {
                this.erroCep = error.message || 'Não foi possível consultar o CEP. Confira e tente novamente.';
            } finally {
                this.buscandoCep = false;
            }
        }
    }"
    class="space-y-4"
>
    <div class="grid sm:grid-cols-3 gap-4">
        <div>
            <label for="cep" class="form-label">CEP</label>
            <div class="relative">
                <input id="cep" type="text" name="cep" value="{{ old('cep') }}" x-model="cep" @input="formatarCep" @blur="buscarCep"
                       inputmode="numeric" autocomplete="postal-code" maxlength="9" class="form-input pr-10" placeholder="00000-000">
                <i x-show="buscandoCep" x-cloak class="ti ti-loader-2 animate-spin absolute right-3.5 top-3 text-brand-600" aria-label="Buscando CEP"></i>
            </div>
            <p x-show="erroCep" x-cloak x-text="erroCep" class="form-error"></p>
            @error('cep') <p class="form-error">{{ $message }}</p> @enderror
        </div>
        <div class="sm:col-span-2">
            <label for="logradouro" class="form-label">Rua / Logradouro</label>
            <input id="logradouro" type="text" name="logradouro" value="{{ old('logradouro') }}" x-ref="logradouro"
                   autocomplete="address-line1" maxlength="75" class="form-input" placeholder="Preencha com sua rua ou logradouro">
            @error('logradouro') <p class="form-error">{{ $message }}</p> @enderror
        </div>
    </div>

    <div class="grid sm:grid-cols-3 gap-4">
        <div>
            <label for="numero" class="form-label">Número</label>
            <input id="numero" type="text" name="numero" value="{{ old('numero') }}" x-ref="numero"
                   autocomplete="address-line2" maxlength="10" class="form-input" placeholder="Ex: 123">
            @error('numero') <p class="form-error">{{ $message }}</p> @enderror
        </div>
        <div class="sm:col-span-2">
            <label for="complemento" class="form-label">Complemento</label>
            <input id="complemento" type="text" name="complemento" value="{{ old('complemento') }}"
                   autocomplete="address-line2" maxlength="35" class="form-input" placeholder="Apto, bloco, casa, lote...">
            @error('complemento') <p class="form-error">{{ $message }}</p> @enderror
        </div>
    </div>

    <div class="grid sm:grid-cols-3 gap-4">
        <div>
            <label for="bairro" class="form-label">Bairro</label>
            <input id="bairro" type="text" name="bairro" value="{{ old('bairro') }}" x-ref="bairro"
                   autocomplete="address-level3" maxlength="45" class="form-input" placeholder="Preencha com seu bairro">
            @error('bairro') <p class="form-error">{{ $message }}</p> @enderror
        </div>
        <div>
            <label for="cidade" class="form-label">Cidade</label>
            <input id="cidade" type="text" name="cidade" value="{{ old('cidade') }}" x-ref="cidade"
                   autocomplete="address-level2" maxlength="45" class="form-input" placeholder="Preencha com sua cidade">
            @error('cidade') <p class="form-error">{{ $message }}</p> @enderror
        </div>
        <div>
            <label for="uf" class="form-label">UF</label>
            <input id="uf" type="text" name="uf" value="{{ old('uf') }}" x-ref="uf" maxlength="2"
                   autocomplete="address-level1" class="form-input uppercase" placeholder="PR">
            @error('uf') <p class="form-error">{{ $message }}</p> @enderror
        </div>
    </div>

    <p class="form-hint">Informe o CEP para preencher cidade e UF automaticamente. Você pode ajustar os dados se necessário.</p>
</div>
