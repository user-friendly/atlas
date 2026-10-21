#                                               -*- Autoconf -*-
# Process this file with autoconf to produce a configure script.

AC_PREREQ([2.73])
AC_INIT([Atlas], [0.1])
AM_INIT_AUTOMAKE([-Wall subdir-objects])
AC_CONFIG_SRCDIR([src/main.cpp])
AC_CONFIG_HEADERS([config.h])

# M4 macros specific to this project.
AC_CONFIG_MACRO_DIR([m4-project])

# Add debug option.
AX_CHECK_ENABLE_DEBUG([no], [DEBUG])
AX_CHECK_ENABLE_PEDANTIC([no])

# Checks for programs.
AC_PROG_CXX([clang++ c++ g++])
AC_PROG_CPP([clang gcc])

# Enable PCH.
#AX_PRECOMPILED_HEADERS([std.h])

# Checks for languages.
AC_LANG([C++])
AC_SUBST([WARN_CXXFLAGS], ["-Wall -Wextra"])

# Checks for libraries.
AX_BOOST_BASE([1.92],
        [],
        [AC_MSG_ERROR([Minimal Boost library requirments not met.])
])

AX_BOOST_FILESYSTEM
AX_BOOST_RANDOM
# AX_BOOST_CHRONO
# AX_BOOST_LOG
# AX_BOOST_THREAD
# AX_BOOST_REGEX
# AX_BOOST_TIMER
dnl At this point, use the shared Boost libraries.
dnl TODO Use static linking later on.
AC_DEFINE([BOOST_ALL_DYN_LINK], [1], [Boost dynamic library link flag])

# Checks for header files.

# Checks for typedefs, structures, and compiler characteristics.
AX_CXX_COMPILE_STDCXX_17([noext], [mandatory])

# Checks for library functions.

AC_CONFIG_FILES([Makefile])
AC_OUTPUT

echo \
"-------------------------------------------------

 ${PACKAGE_NAME} Version ${PACKAGE_VERSION}

 Prefix: '${prefix}'.
 Variables:
 
 ac_top_srcdir= ${ac_top_srcdir}
 
 -- Compiler
 CC: ${CC}
 CXX: ${CXX}
 CPP: ${CPP}
 CFLAGS: ${CFLAGS}
 CPPFLAGS: ${CPPFLAGS}
 CXXFLAGS: ${CXXFLAGS}
 
 -- Precompiled headers
 CFLAGS_PCH: ${CFLAGS_PCH}
 CXXFLAGS_PCH: ${CXXFLAGS_PCH}
 PCH_FILENAME: ${PCH_FILENAME}
 PCH_SOURCE_FILENAME: ${PCH_SOURCE_FILENAME}

 -- Boost
 BOOST_CPPFLAGS: ${BOOST_CPPFLAGS}
 BOOST_LDFLAGS: ${BOOST_LDFLAGS}

 -- Linker
 LDFLAGS: ${LDFLAGS}
 LIBOBJS: ${LIBOBJS}
 LIBS: ${LIBS}
 LTLIBOBJS: ${LTLIBOBJS}

 Now type 'make @<:@<target>@:>@'
   where the optional <target> is:
     all                - build all binaries

--------------------------------------------------"
