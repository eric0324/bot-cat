<?php
/**
 * @package BotCat\Analytics
 */

declare(strict_types=1);

namespace BotCat\Analytics;

/**
 * Renders a 2D table as a UTF-8 CSV with BOM.
 *
 * The BOM (`EF BB BF`) is what makes Microsoft Excel auto-detect UTF-8
 * on open — without it, Big5-default Excel mojibakes CJK titles.
 */
class CsvExporter {

	public const BOM = "\xEF\xBB\xBF";

	/**
	 * @param list<string>           $headers
	 * @param list<list<int|string>> $rows
	 */
	public function to_string( array $headers, array $rows ): string {
		$fh = fopen( 'php://temp', 'r+' );
		if ( ! is_resource( $fh ) ) {
			return self::BOM;
		}

		fputcsv( $fh, $headers );
		foreach ( $rows as $row ) {
			fputcsv( $fh, array_map( static fn( $v ): string => (string) $v, $row ) );
		}

		rewind( $fh );
		$body = (string) stream_get_contents( $fh );
		fclose( $fh );

		return self::BOM . $body;
	}

	/**
	 * @param list<string>           $headers
	 * @param list<list<int|string>> $rows
	 */
	public function send( string $filename, array $headers, array $rows ): void {
		if ( ! headers_sent() ) {
			header( 'Content-Type: text/csv; charset=UTF-8' );
			header( 'Content-Disposition: attachment; filename="' . rawurlencode( $filename ) . '"' );
		}
		echo $this->to_string( $headers, $rows ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}
}
