<?php

class Minify
{
	/**
	 * Minify HTML
	 *
	 * @param string $html
	 * @return string
	 */
	public static function html ($html)
	{
		// This regex was taken from http://stackoverflow.com/a/5324014/196106
		$re = '%# Collapse whitespace everywhere but in blacklisted elements.
		(?>             # Match all whitespans other than single space.
		  [^\S ]\s*     # Either one [\t\r\n\f\v] and zero or more ws,
		| \s{2,}        # or two or more consecutive-any-whitespace.
		) # Note: The remaining regex consumes no text at all...
		(?=             # Ensure we are not in a blacklist tag.
		  [^<]*+        # Either zero or more non-"<" {normal*}
		  (?:           # Begin {(special normal*)*} construct
			<           # or a < starting a non-blacklist tag.
			(?!/?(?:textarea|pre|code|script)\b)
			[^<]*+      # more non-"<" {normal*}
		  )*+           # Finish "unrolling-the-loop"
		  (?:           # Begin alternation group.
			<           # Either a blacklist start tag.
			(?>textarea|pre|code|script)\b
		  | \z          # or end of file.
		  )             # End alternation group.
		)  # If we made it here, we are not in a blacklist tag.
		%Six';

		$out = preg_replace($re, ' ', $html);
		$out = ($out === null) ? $html : $out;

		$out = preg_replace('/<!--[^\[>\/].*?-->/', '', $out);
		return ($out === null) ? $html : $out;
	}
}

