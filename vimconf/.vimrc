"---first set up ----"
set nocompatible "vi compatible off
set history=1000 "saving max vi command history count
set filetype=on "used by "autocmd" option,file suffix check on
set directory=. "directory for saving tmp editing file
set title "for vim function if you use "titlestring" variable

"file type detection
augroup filetypedetect
	autocmd BufNewFile,BufRead *.txt :setf txt
	autocmd BufNewFile,BufReadPre *.howm :setf fileformats=dos,unix
	autocmd BufNewFile,BufReadPost *.pl :set filetype=perl
	autocmd BufNewFile,BufReadPost *.pm :set filetype=perl
	autocmd BufNewFile,BufReadPost *Test.php :set filetype=phpunit
	autocmd BufNewFile,BufReadPost *.php :set filetype=php
	autocmd BufNewFile,BufReadPost *.frm :set filetype=php
	autocmd BufNewFile,BufReadPost *.js :set filetype=javascript
"	autocmd BufNewFile,BufReadPost *.tpl :set filetype=html
	autocmd BufNewFile,BufReadPost *.htm :set filetype=html
	autocmd BufNewFile,BufReadPost *.html :set filetype=html
	autocmd BufNewFile,BufReadPost *.xml :set filetype=xml
	autocmd BufNewFile,BufReadPost *.java :set filetype=java
	autocmd BufNewFile,BufReadPost *.rb :set filetype=ruby
	autocmd BufNewFile,BufReadPost *.rs :set filetype=rspec
augroup END

"---/first set up ----"

"--- compile action ----"
autocmd BufNewFile,BufRead *.pl map <F5> <ESC>:!perl %:p<CR>
autocmd BufNewFile,BufRead *.pm map <F5> <ESC>:!perl %:p<CR>
autocmd BufNewFile,BufRead *.php map <F5> <ESC>:!php %:p<CR>
autocmd BufNewFile,BufRead *Test.php map <F5> <ESC>:!phpunit %<<CR>
"autocmd BufNewFile,BufRead *.java map <F5> <ESC>:!javac %:.<CR>:!java %:.:r<CR>
autocmd BufNewFile,BufRead *.java map <F5> <ESC>:!javac %:.<CR>
autocmd BufNewFile,BufRead *.java map <F6> <ESC>:!java %:.:r<CR>
autocmd BufNewFile,BufRead *.java map <F7> <ESC>:!ctags_java.sh %:p:h<CR>

autocmd BufNewFile,BufRead *.sh map <F5> <ESC>:!bash %:p<CR>
autocmd BufNewFile,BufRead *.rb map <F5> <ESC>:!ruby %:p<CR>
autocmd BufNewFile,BufRead *.rs map <F5> <ESC>:!rspec %:p<CR>
"--- /compile action ----"

"--- terminal view ----"
set ruler "display cursor position ruler 
set cursorline "display cursor line
hi CursorLine ctermfg=White "change cursor color "while"
set number "display line number
set wildchar=<Tab> "see under wild menu
set wildmenu "display selecting intelligent completing vim command list if you input char and wild char
set wildmode=list:full "display all intelligent completable command menu list"
set cmdheight=1 "command line height under vim command mode
set showcmd "show vim command.when mode is visual mode, display selecting line number or etc.
set showmode "show vim mode.

syntax on
hi Visual ctermfg=yellow " visual mode hilight
hi SpecialKey ctermfg=Magenta "meta on! HighlightDislikeSpace()
hi Pmenu ctermbg=DarkMagenta "popup completion menu bgcolor
hi PmenuSel ctermbg=DarkBlue "popup completion selecting menu bgcolor
hi PmenuSbar ctermbg=DarkRed "popup completion selecting menu scroll bar


" 全角スペース
" スペース類をハイライトする
function! HighlightDislikeSpace()
	syntax match DislikeSpace /　/
	" 行末のスペース
	syntax match DislikeSpace /\S\@<=\s\+$/
	hi DislikeSpace cterm=underline ctermbg=Magenta
endfunc

autocmd BufNew,BufRead * call HighlightDislikeSpace()
"--- /terminal view ----"


"--- terminal encoding ----"
set encoding=utf-8 "vim inside encoding, so normally encoding=termencoding
let &termencoding = &encoding "io encoding type
let &fileencodings = &encoding "read and write file encoding,normally includes buffer read and write. so, io is keyboard(display) -> termencoding -> encoding-> fileencoding.
"--- /terminal encoding ----"

"--- keybord action ----"
set backspace=indent,eol,start "BS key action.
set autoindent "set autoindent
set shiftwidth=2 "set autoindent width
set softtabstop=4 "if you input tab key,setted number spaces is filled.
set tabstop=4 "this is linked with softtabstop. ex,when softtabstop is 2 and tabstop is 4,1 tab input is displayed as a 2 spaces,2 tab input is displayed as a tab.
set smartindent "if you input bracket ant enter key, indent is setted.


"--- set input mode map action ----"
"{ bracket imap
imap { {	}<ESC>k$A
"[ bracket imap
imap [ []<LEFT>
"( bracket imap
imap ( ()<LEFT>
"" bracket imap
imap " "<ESC>:call AppendQuoteChar("\"")<ENTER>i<RIGHT>
"' bracket imap
imap ' '<ESC>:call AppendQuoteChar("'")<ENTER>i<RIGHT>
"--- /set input mode map action ----"

function! AppendQuoteChar(char)
	let s:addchar = a:char
	let s:linestr = getline(".")
	let s:cursor_count = col(".")
	let s:setstr = ""
	let s:index = 0
	let s:isappended = 0
	while s:index < strlen(s:linestr)
		let s:charat = strpart(s:linestr, s:index , 1)

		if ( s:charat == " " || s:charat == "," || s:charat == ")" ) && !s:isappended && s:index > s:cursor_count
			let s:setstr = s:setstr . s:addchar
			let s:isappended = 1
		endif
		let s:setstr = s:setstr . s:charat
		let s:index = s:index + 1
	endwhile

	if !s:isappended
		let s:setstr = s:setstr . s:addchar
	endif

	call setline("." , s:setstr)
endfunc

function! CommentOutOneLine()
	let s:linestr = getline(".")
	let s:index = 0
	let s:isstart = 0
	let s:ismode = 0
	while s:index < strlen(s:linestr)
		let s:charat = strpart(s:linestr, s:index , 1)
		if ( s:charat == " " || s:charat == "	" )
		  let s:index = s:index + 1
		  continue
		endif

		if ( s:charat == "/" && !s:isstart )
		  let s:index = s:index + 1
		  let s:isstart = 1
		  continue
		endif
		if ( s:charat == "/" && s:isstart )
		  let s:index = s:index + 1
		  let s:ismode = 1
		  break
		endif
		if ( s:charat != "/" )
		  let s:ismode = 0
		  break
		endif
		break
	endwhile

	if ( s:ismode )
	  let s:setstr = strpart(s:linestr, s:index )
	else
	  let s:setstr = "//" . s:linestr
	endif

	call setline("." , s:setstr)
endfunc



"--- set visual mode map action ----"
"--- /set visual mode map action ----"

"--- completion set----"
function! InsertTabWrapper()
	if pumvisible()
		return "\<c-n>"
	endif
	let col= col('.') - 1
	if !col || getline('.')[col - 1] !~ '\k\|<\|/'
		return "\<tab>"
	elseif exists('&omnifunc') && &omnifunc == ''
		return "\<c-n>"
	else
		return "\<c-x>\<c-o>"
	endif
endfunc
		
imap <tab> <c-r>=InsertTabWrapper()<cr>
"imap" <C-P> <C-X><C-K>
map <C-P> :bprev<CR>
cnoremap <C-D> <DEL>
cnoremap <C-H> <LEFT>
cnoremap <C-L> <RIGHT>
nnoremap <tab> :tabnext<CR>
nnoremap <C-N> :tabnew<CR>

imap <C-E> <ESC>:call CommentOutOneLine()<ENTER>i<RIGHT>

"--- /completion set----"

"/--- keybord action ----"

"--- search action ----"
set magic "set regular expression
set incsearch "set increment search
set ignorecase "set no checking upper and lower alphabet
set smartcase "linked width ignorecase.if matching word include upper char,check upper alphabet.
set showmatch "diplay bracket matching.
set hlsearch "set displaying matching char hilight
"--- /search action ----"

"--- command action ----"
set makeprg=gmake "command "makeprg" exec unix command "gmake"
"--- /command action ----"

"--- complete action ----"
set complete=.,w,b,u,t,i,k "for intelligent completion, use scan the current buffer,other windows,other buffers list,unloaded buffers,files linked "dictionary" option ,current and included files ,tag completion"
set completeopt=menu,preview "use a popup menu to show the possible completions. Show extra information about the currently selected."

"completion set
autocmd FileType py set omnifunc=pythoncomplete#Complete
autocmd FileType js set omnifunc=javascriptcomplete#CompleteJS
autocmd FileType html set omnifunc=htmlcomplete#CompleteTags
autocmd FileType css set omnifunc=csscomplete#CompleteCSS
autocmd FileType xml set omnifunc=xmlcomplete#CompleteTags
autocmd FileType php set omnifunc=phpcomplete#CompletePHP
autocmd FileType cpp set omnifunc=ccomplete#Complete
autocmd FileType pl :set dictionary+=~/.vim/dictionary/perl.dict
"autocmd FileType phpunit :set dictionary+=~/.vim/dictionary/phpunit.dict
autocmd FileType ruby set omnifunc=rubycomplete#Complete
autocmd FileType rspec set omnifunc=rubycomplete#Complete

autocmd FileType java :setlocal omnifunc=javacomplete#Complete
autocmd FileType java :set omnifunc=
autocmd FileType java :setlocal completefunc=javacomplete#CompleteParamsInfo
autocmd FileType java :set dictionary+=~/.vim/dictionary/j2se14.dict
autocmd FileType java :set tags=~/.tags/javapjt,~/.tags/java

set complete+=k
"--- /complete  ----"


"--- error action ----"
"set visualbell t_vb=
"--- /error action ----"

"http://php.benscom.com/manual/ja/function.array-pad.php"


"--- display html page or file via w3m ----"
function! DisplayPageByW3M(uri)
	let s:requesturi = a:uri
	let s:cmd = ".!w3m " . s:requesturi
	exe s:cmd
endfunc
"--- /display html page or file via w3m ----"

"--- get function name ----"
function! GetFunctionName()
	let s:linestr = getline(".")
	let s:cursor_count = col(".") -1
	let s:setstr = ""
	let s:index = 0
	while s:index < strlen(s:linestr)
	  let s:charat = strpart(s:linestr, s:index , 1)
		if ( match(s:charat , "[a-zA-Z0-9_]" ) == -1 )
		  if ( s:index >= s:cursor_count )
			break
		  else
			let s:setstr = ""
		  endif
		else
		  let s:setstr = s:setstr . s:charat
		endif
	  let s:index = s:index + 1
	endwhile
	return s:setstr
endfunc

function! DisplayHowToPageByPHP()
	let s:funcstr = GetFunctionName()
	let s:optstr = ''
	let s:funcstr = substitute(s:funcstr,'_','-',"g")
	let s:funcstr = s:optstr . " " . "http://php.benscom.com/manual/ja/function." . s:funcstr . ".php"
	exe ":new ~/.vim/preview"
	call DisplayPageByW3M(s:funcstr)
	exe ":w"
endfunc

function! SetUpJava()
	let java_highlight_functions="style"
	let java_allow_cpp_keywords=1
	let java_highlight_debug=1
	let java_space_errors=1
endfunc

"--- set visual mode map action ----"

"--- set visual mode map action ----"
"autocmd FileType python imap set omnifunc=pythoncomplete#Complete
"autocmd FileType javascript set omnifunc=javascriptcomplete#CompleteJS
"autocmd FileType html set omnifunc=htmlcomplete#CompleteTags
"autocmd FileType css set omnifunc=csscomplete#CompleteCSS
"autocmd FileType xml set omnifunc=xmlcomplete#CompleteTags
autocmd FileType php map <F8> <ESC>:call DisplayHowToPageByPHP()<ENTER><ESC>:107<ENTER>
autocmd FileType php imap <F8> <ESC>:call DisplayHowToPageByPHP()<ENTER><ESC>:107<ENTER>
"autocmd FileType c set omnifunc=ccomplete#Complete
"autocmd FileType perl :set dictionary+=~/.vim/dictionary/perl.dict
"autocmd FileType phpunit :set dictionary+=~/.vim/dictionary/phpunit.dict

autocmd FileType java imap <buffer> <C-X><C-U> <C-X><C-U><C-P> 
autocmd FileType java imap <buffer> <C-S-Space> <C-X><C-U><C-P> 

function! CompileJava()
":make %
:!javac %:.
:!java %<
"<CR>:!java %:.:r<CR>
endfunction

au FileType java compiler javac
"au FileType java map <F5> :call CompileJava()<CR>
"autocmd FileType java set filetype='java'
"au" FileType java let java_highlight_debug=1
"#autocmd" FileType java let java_highlight_all=1
"autocmd" BufNewFile,BufReadPost *.java :set java_highlight_all=1


if( expand('%:e') == 'java' )
  let java_highlight_all=1
  let java_highlight_functions="style"
  let java_allow_cpp_keywords=1
  let java_highlight_debug=1
  let java_space_errors=1
endif

function! FileFullName()
  echo expand('%:p')
endfu


function! PathName()
  echo expand('%:p:h')
endfu

function! SuffixName()
  echo expand('%:e')
endfu

function! FileName()
  echo expand('%:r')
endfu
