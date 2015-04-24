<?php
/**
 * Graphs
 *
 * This class handles building pretty report graphs, based on EDD_Graph of Pippin Williamson
 *
 * @package     Seo Monitor
 * @subpackage  Admin/Reports
 * @copyright   Copyright (c) 2015, To Be On The Web
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       1.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Seo_Monitor_Graph Class
 *
 * @since 1.0
 */
class Seo_Monitor_Graph {

	/*

	Simple example:

	data format for each point: array( location on x, location on y )

	$data = array(

		'Label' => array(
			array( 1, 5 ),
			array( 3, 8 ),
			array( 10, 2 )
		),

		'Second Label' => array(
			array( 1, 7 ),
			array( 4, 5 ),
			array( 12, 8 )
		)
	);

	$graph = new Seo_Monitor_Graph( $data );
	$graph->display();

	*/

	/**
	 * Data to graph
	 *
	 * @var array
	 * @since 1.0
	 */
	private $data;

	/**
	 * Unique ID for the graph
	 *
	 * @var string
	 * @since 1.0
	 */
	private $id = '';

	/**
	 * Graph options
	 *
	 * @var array
	 * @since 1.0
	 */
	private $options = array();

	/**
	 * Get things started
	 *
	 * @since 1.0
	 */
	public function __construct( $_data ) {

		$this->data = $_data;

		// Generate unique ID
		$this->id   = md5( rand() );

		// Setup default options;
		$this->options = array(
			'y_mode'          => null,
			'x_mode'          => null,
			'y_decimals'      => 0,
			'x_decimals'      => 0,
			'y_position'      => 'left',
			'time_format'     => '%d/%b',
			'ticksize_unit'   => 'day',
			'ticksize_num'    => 1,
			'multiple_y_axes' => false,
			'bgcolor'         => '#fff',
			'bordercolor'     => '#ccc',
			'color'           => '#000',
			'borderwidth'     => 0,
			'bars'            => false,
			'lines'           => true,
			'points'          => true
		);

	}

	/**
	 * Set an option
	 *
	 * @param $key The option key to set
	 * @param $value The value to assign to the key
	 * @since 1.0
	 */
	public function set( $key, $value ) {
		$this->options[ $key ] = $value;
	}

	/**
	 * Get an option
	 *
	 * @param $key The option key to get
	 * @since 1.0
	 */
	public function get( $key ) {
		return isset( $this->options[ $key ] ) ? $this->options[ $key ] : false;
	}

	/**
	 * Get graph data
	 *
	 * @since 1.0
	 */
	public function get_data() {
		return apply_filters( 'seo_monitor_get_graph_data', $this->data, $this );
	}

	/**
	 * Load the graphing library script
	 *
	 * @since 1.0
	 */
	public function load_scripts() {
		wp_enqueue_script( 'jquery-flot', plugin_dir_url( __FILE__ ) . '../../assets/js/jquery.flot.min.js', array( 'jquery' ), false, false );
		wp_enqueue_script( 'jquery-flot-time', plugin_dir_url( __FILE__ ) . '../../assets/js/jquery.flot.time.min.js', array( 'jquery' ), false, false );
	}

	/**
	 * Build the graph and return it as a string
	 *
	 * @var array
	 * @since 1.0
	 * @return string
	 */
	public function build_graph() {

		$yaxis_count = 1;

		$this->load_scripts();

		ob_start();
?>
		<script type="text/javascript">
			jQuery( document ).ready( function($) {
				$.plot(
					$("#seo-monitor-graph-<?php echo $this->id; ?>"),
					[
						<?php foreach( $this->get_data() as $label => $data ) :	?>
						{
							label: "<?php echo esc_attr( $label ); ?>",
							id: "<?php echo sanitize_key( $label ); ?>",
							shadowSize: 0,
							highlightColor: "#2F7ED8",
							// data format is: [ point on x, value on y ]
							data: [<?php foreach( $data as $point ) { echo '[' . implode( ',', $point ) . '],'; } ?>],
							points: {
								show: <?php echo $this->options['points'] ? 'true' : 'false'; ?>,
								symbol: "circle",
								radius: 4
							},
							bars: {
								show: <?php echo $this->options['bars'] ? 'true' : 'false'; ?>,
								barWidth: 12,
								aling: 'center'
							},
							lines: {
								show: <?php echo $this->options['lines'] ? 'true' : 'false'; ?>,
								fillColor: "#2F7ED8",
								lineWidth: 2,
								fill: false,
								//zero: false
							},
							<?php if( $this->options['multiple_y_axes'] ) : ?>
							yaxis: <?php echo $yaxis_count; ?>
							<?php endif; ?>
						},
						<?php $yaxis_count++; endforeach; ?>
					],
					{
						// Options
						grid: {
							show: true,
							aboveData: false,
							color: "<?php echo $this->options['color']; ?>",
							backgroundColor: "<?php echo $this->options['bgcolor']; ?>",
							borderColor: "<?php echo $this->options['bordercolor']; ?>",
							borderWidth: <?php echo absint( $this->options['borderwidth'] ); ?>,
							clickable: false,
							hoverable: true
						},
						xaxis: {
							mode: "<?php echo $this->options['x_mode']; ?>",
							timeformat: "<?php echo $this->options['x_mode'] == 'time' ? $this->options['time_format'] : ''; ?>",
							<?php
								if ( $this->options['x_mode'] == 'time' ) {  ?>
									tickSize: [2, "day"],
								<?php } else { ?>
									tickSize: 1,
									tickDecimals: <?php echo $this->options['x_decimals']; ?>,
								<?php }
							?>
							tickLength: 0, //to hide vertical grid lines
						},
						yaxis: {
							position: 'left',
							transform: function(v) { v = Math.log(v); return -v; },
    						inverseTransform: function(v) { return -v; },
							min: 1,
							//max: 110,
							mode: "<?php echo $this->options['y_mode']; ?>",
							timeformat: "<?php echo $this->options['y_mode'] == 'time' ? $this->options['time_format'] : ''; ?>",
							ticks: [1, 2, 4, 10, 20, 40, 100, [110, "No Rank"]],
							/*
							ticks: function(axis) {
								var res=[];
							    //var res = [], i = Math.floor(axis.min / Math.PI);
							    //do {
							    //    var v = i * Math.PI;
							    //    res.push([v, i + "\u03c0"]);
							    //    ++i;
							    //} while (v < axis.max);
								res.push(axis.min);
								if(axis.max == 110)
									res.push([110, "No Rank"]);
								else res.push(axis.max);
								return res;
							},
							*/
							//autoscaleMargin: 0.1,
							//tickSize: 15,
							//minTickSize: 10,
							//tickLength: 120,
							//reserveSpace: 120,
							//tickFormatter: function(val, axis) { if(val > 100) { return val = 'No Rank'; } else { return val; } },
							<?php if( $this->options['y_mode'] != 'time' ) : ?>
							tickDecimals: <?php echo $this->options['y_decimals']; ?>
							<?php endif; ?>
						},
						colors: ["#2F7ED8"]
					}

				);

				function seo_monitor_flot_tooltip(x, y, contents) {
					$('<div id="seo-monitor-flot-tooltip">' + contents + '</div>').css( {
						position: 'absolute',
						display: 'none',
						top: y + 5,
						left: x + 5,
						border: '1px solid #fdd',
						padding: '2px',
						'background-color': '#fee',
						opacity: 0.80
					}).appendTo("body").fadeIn(200);
				}

				$("#seo-monitor-graph-<?php echo $this->id; ?>").bind("plothover", function (event, pos, item) {
					if (item) {
						$("#seo-monitor-flot-tooltip").remove();
						var x = item.datapoint[0];
						var y = item.datapoint[1];

						if(y == 110) {
							y = 'No Rank';
						}

						seo_monitor_flot_tooltip( item.pageX, item.pageY, item.series.label + ': ' + y );
					} else { //remove tooltip when no point is selected
						$("#seo-monitor-flot-tooltip").remove();
					}
				});

			});

		</script>
		<div id="seo-monitor-graph-<?php echo $this->id; ?>" class="seo-monitor-graph" style="width: 80%;height: 300px;"></div>
<?php
		return ob_get_clean();
	}

	/**
	 * Output the final graph
	 *
	 * @since 1.0
	 */
	public function display() {
		do_action( 'seo_monitor_before_graph', $this );
		echo $this->build_graph();
		do_action( 'seo_monitor_after_graph', $this );
	}

}