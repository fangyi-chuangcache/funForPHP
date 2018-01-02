<?php
$pid = pcntl_fork();
if ($pid == -1) {
    die("could not fork");
} else if ($pid == 0) {
    echo "I'm the child  process \n";
} else {
    echo "I'm the parent process \n";
    exit;
}
/*
 * 一个进程，主要包含三个元素：
1. 一个可以执行的程序；
2. 和该进程相关联的全部数据（包括变量，内存空间，缓冲区等等）；
3. 程序的执行上下文（execution context）；

一个进程表示的就是一个可执行程序的一次执行过程中的一个状态。
操作系统对进程的管理，典型的情况，是通过进程表完成的。
进程表中的每一个表项，记录的是当前操作系统中一个进程的情况。
对于单 CPU的情况而言，每一特定时刻只有一个进程占用 CPU，但是系统中可能同时存在多个活动的（等待执行或继续执行的）进程。

一个称为”程序计数器（program counter, pc）”的寄存器，指出当前占用 CPU的进程要执行的下一条指令的位置。

当分给某个进程的 CPU时间已经用完，操作系统将该进程相关的寄存器的值，保存到该进程在进程表中对应的表项里面；把将要接替这个进程占用 CPU的那个进程的上下文，从进程表中读出，并更新相应的寄存器（这个过程称为”上下文交换(process context switch)”，实际的上下文交换需要涉及到更多的数据，那和fork无关，不再多说，主要要记住程序寄存器pc指出程序当前已经执行到哪里，是进程上下文的重要内容，换出 CPU的进程要保存这个寄存器的值，换入CPU的进程，也要根据进程表中保存的本进程执行上下文信息，更新这个寄存器）。

当你的程序执行到下面的语句：
pid = pcntl_fork();
操作系统创建一个新的进程（子进程），并且在进程表中相应为它建立一个新的表项。
新进程和原有进程的可执行程序是同一个程序；上下文和数据，绝大部分就是原进程（父进程）的拷贝，但它们是两个相互独立的进程！
此时程序寄存器pc在父、子进程的上下文中都声称，这个进程目前执行到fork调用即将返回（此时子进程不占有CPU，子进程的pc不是真正保存在寄存器中，而是作为进程上下文保存在进程表中的对应表项内）。问题是怎么返回，在父子进程中就分道扬镳。

父进程继续执行操作系统对fork的实现，使这个调用在父进程中返回刚刚创建的子进程的pid（一个正整数），所以后面的if语句中pid<0, pid==0的两个分支都不会执行。所以输出：i am the parent process…

接着子进程在之后的某个时候得到调度，它的上下文被换入，占据 CPU，操作系统对fork的实现使得子进程中fork调用返回0，所以在这个进程中pid=0（注意这不是父进程了，虽然是同一个程序，但是这是同一个程序的另外一次执行，在操作系统中这次执行是由另外一个进程表示的，从执行的角度说和父进程相互独立）。这个进程在继续执行的过程中，if语句中 pid<0不满足，但是pid==0是true，所以输出：i am the child process…

为什么看上去程序中互斥的两个分支都被执行了，在一个程序的一次执行中，这当然是不可能的，事实上你看到的两行输出是来自两个独立的进程，而这两个进程来自同一个程序的两次执行。

fork之后，操作系统会复制一个与父进程完全相同的子进程，虽说是父子关系，但是在操作系统看来，他们更像兄弟关系，这2个进程共享代码空间，但是数据空间是互相独立的，子进程数据空间中的内容是父进程的完整拷贝，指令指针也完全相同，但只有一点不同，如果fork成功，子进程中fork的返回值是0，父进程中fork的返回值是子进程的进程号，如果fork失败，父进程会返回错误。
可以这样想象，2个进程一直同时运行，而且步调一致，在fork之后，他们分别作不同的工作，也就是分岔了，这也是fork为什么叫fork的原因。
至于哪一个进程最先运行，这与操作系统平台的调度算法有关，而且这个问题在实际应用中并不重要，如果需要父子进程协同运作，可以通过控制语法结构的办法解决。

fork前子进程可以继承父进程的东西，但是在pcntl_fork()后子进程和父进程就没有任何继承关系了。在子进程里创建的东西是子进程的，在父进程创建的东西是父进程的，可以完全看成是两个独立的进程。

在程序段里用了pcntl_fork()之后程序出了分岔，派生出了两个进程，具体哪个先运行就看该系统的调度算法了。
在这里，我们可以这么认为，在运行到”pid=pcntl_fork();”时系统派生出一个跟主程序一模一样的子进程。该进程的”pid=pcntl_fork();”一句中 pid得到的就是子进程本身的pid；子进程结束后，父进程的”pid=pcntl_fork();”中pid得到的就是父进程本身的pid，因此该程序有两行输出。

pcntl_fork()函数复制了当前进程的PCB，并向父进程返回了派生子进程的pid，父子进程并行，打印语句的先后完全看系统的调度算法，打印的内容控制则靠pid变量来控制。因为我们知道pcntl_fork()向父进程返回了派生子进程的pid，是个正整数；而派生子进程的pid变量并没有被改变，这一区别使得我们看到了他们的不同输出。

1. 派生子进程的进程，即父进程，其pid不变；
2. 对子进程来说，fork()函数返回给它0, 但它自身的pid绝对不会是0；之所以fork()函数返回0给它，是因为它随时可以调用getpid()来获取自己的pid；
3. fork之后父、子进程除非采用了同步手段，否则不能确定谁先运行，也不能确定谁先结束。认为子进程结束后父进程才从fork返回的，这是不对的，fork不是这样的，vfork才这样。

 */