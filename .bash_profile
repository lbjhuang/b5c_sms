function git_branch {
   branch="`git branch 2>/dev/null | grep "^\*" | sed -e "s/^\*\ //"`"
   if [ "${branch}" != "" ];then
       if [ "${branch}" = "(no branch)" ];then
           branch="(`git rev-parse --short HEAD`...)"
       fi
       echo " ($branch)"
   fi
}

export PS1='\033[01;32m\u@\h:\[\033[01;34m\]\w\[\033[01;32m\]$(git_branch)\[\033[00m\] \$ '

// mac
export PS1='\033[0;32m\u@\h:\[\033[01;34m\]\w\[\033[0;32m\]$(git_branch)\[\033[00m\] \$ '
[ -r ~/.bashrc ] && source ~/.bashrc
