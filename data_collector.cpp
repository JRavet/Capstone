#include <iostream>
//cURLpp
#include <curlpp/cURLpp.hpp>
#include <curlpp/Easy.hpp>
#include <curlpp/Options.hpp>
//strings and string-processing
#include <string.h>
#include <sstream>
//JSONcpp
#include <jsoncpp/json/json.h>
//mySQL Connector/c++
#include "mysql_connection.h"
#include <cppconn/driver.h>
#include <cppconn/exception.h>
#include <cppconn/resultset.h>
#include <cppconn/statement.h>
#include "mysql_driver.h"
//timing
#include <ctime>
//
#define FIRST_SRV "green"
#define SECOND_SRV "blue"
#define THIRD_SRV "red"
#define TIME_RES 60 //in seconds; standard is 60 seconds
#define MICROSEC 1000000.0 //the amount of microseconds in a second
//
#define IPADDR "tcp://127.0.0.1:3306"
#define USERNAME "root"
#define PASSWORD "egamirrorimeht"
#define DATABASE "Gw2Analyser"
/* */
using namespace cURLpp;
using namespace Options;
using namespace std;
/* */
bool stored_matchDetails = false;
bool connected = true; //TODO for the database connection
bool force_resync = false;
string previous_start_time = "";
//TODO comment functions
//TODO rename variables to be more descriptive
//TODO multithread
//TODO calc weeknum
//TODO calc tick_timer backwards from current
//TODO grn/blu/red_srv_populations in storing match_details
/* */
void convertNumToString(stringstream *converter, float valueToConvert, string *returnString)
{
	(*converter) << valueToConvert;
	(*returnString) += converter->str();
	converter->str("");
	converter->clear();
}
/* */
void check_guildClaim(string guildID, sql::Connection *con)
{
	sql::Statement *stmt;
	sql::ResultSet *res;
	stmt = con->createStatement();
	try
	{
		res = stmt->executeQuery("select * from guild where guild_id=\"" + guildID +"\"");
	}
	catch (sql::SQLException &e)
	{
	//	cout << e.what() << endl;
		return; //return from the function early
	}
	if (!res->next()) //if there were no matching guild ids ...
	{
		Easy request;
		stringstream guildDetails;
		Json::Value guild_data;
		Json::Reader reader;
		/* */
		request.setOpt(cURLpp::Options::WriteStream(&guildDetails));
		request.setOpt(Url("https://api.guildwars2.com/v1/guild_details.json?guild_id=" + guildID));		
		try
		{
			request.perform();
			/* */
			if (reader.parse(guildDetails.str(), guild_data))
			{
				try
				{
					stmt->execute("INSERT INTO guild VALUES(\"" + guildID + "\",\"" + guild_data["guild_name"].asString() + "\",\"" + guild_data["tag"].asString() + "\");");
				}
				catch (sql::SQLException &e)
				{
				//	cout << e.what() << endl;
				}
			}
		}
		catch (exception &e)
		{
			force_resync = true;
			cout << e.what() << endl;
		}
	}
	delete stmt;
	delete res;
}
/* */
void store_activityData(const Json::Value *match_data, int mapNum, sql::Connection *con, double ingame_clock_time)
{
	stringstream converter;
	sql::Statement *stmt;
	string SQLstmt = "";
	const Json::Value objectives = (*match_data)["maps"][mapNum]["objectives"];
	for (int i = 0; i < (int)objectives.size(); i++)
	{
		SQLstmt = "INSERT INTO activity_data VALUES(";
		SQLstmt += "\"" + objectives[i]["last_flipped"].asString() + "\"";
		SQLstmt += ",\"" + objectives[i]["id"].asString() + "\",";
		//
		if (objectives[i]["owner"].asString() == "Red")
		{
			convertNumToString(&converter,(*match_data)["worlds"][THIRD_SRV].asInt(),&SQLstmt);
		}
		else if (objectives[i]["owner"].asString() == "Blue")
		{
			convertNumToString(&converter,(*match_data)["worlds"][SECOND_SRV].asInt(),&SQLstmt);
		}
		else if (objectives[i]["owner"].asString() == "Green")
		{
			convertNumToString(&converter,(*match_data)["worlds"][FIRST_SRV].asInt(),&SQLstmt);
		}
		else
		{
			SQLstmt += "0"; //no PPT value
		}
		//TODO: time_owned / claimed
		/*
			if this.key != max(last_flipped).key
			select max(last_flipped) from activity_data where match_id = "1-4" and obj_id = "38-2";
		*/
		check_guildClaim(objectives[i]["claimed_by"].asString(),con);
		SQLstmt += ",\"" + objectives[i]["claimed_by"].asString() + "\",";
		convertNumToString(&converter,ingame_clock_time,&SQLstmt);
		SQLstmt += ",\"" + objectives[i]["owner"].asString() + "\"";
		SQLstmt += ",\"" + objectives[i]["claimed_at"].asString() + "\"";
		SQLstmt += ",\"" + (*match_data)["id"].asString() + "\"";
		SQLstmt += ",\"" + (*match_data)["start_time"].asString() + "\"";
		SQLstmt += ");";
		try
		{
			stmt = con->createStatement();
			stmt->execute(SQLstmt);
		}
		catch (sql::SQLException &e)
		{
			//cout << e.what() << endl;
		}
		delete stmt;
	}
}
/* */
void get_server_populations(int grn_srv, int blu_srv, int red_srv, string *SQLstmt)
{
	Easy request;
	stringstream result;
	Json::Value server_populations;
	Json::Reader parser;
	/* */
	request.setOpt(cURLpp::Options::WriteStream(&result));
	string requestURL = "https://api.guildwars2.com/v2/worlds?ids=";
	convertNumToString(&result,grn_srv,&requestURL);
	requestURL += ",";
	convertNumToString(&result,blu_srv,&requestURL);
	requestURL += ",";
	convertNumToString(&result,red_srv,&requestURL);
	request.setOpt(Url(requestURL));
	try
	{
		request.perform();
		/* */
		if (parser.parse(result.str(), server_populations))
		{
			(*SQLstmt) += ",\"" + server_populations[0]["population"].asString() + "\"";
			(*SQLstmt) += ",\"" + server_populations[1]["population"].asString() + "\"";
			(*SQLstmt) += ",\"" + server_populations[2]["population"].asString() + "\"";
		}
	}
	catch (exception &e)
	{
		cout << e.what() << endl;
		force_resync = true;
	}
}
/* */
void store_matchDetails(const Json::Value *match_data, string region, sql::Connection *con)
{
	stringstream converter;
	sql::Statement *stmt;
	string SQLstmt;
	for (int i = 0; i < (int)(*match_data).size(); i++)
	{
		if ((((*match_data)[i]["id"]).asString())[0] == region[0])
		{
			SQLstmt = "INSERT INTO match_details VALUES(";
			SQLstmt += "\"" + (*match_data)[i]["id"].asString() + "\"";
			SQLstmt += ",3"; //weekNumber
			SQLstmt += ",\"" + (*match_data)[i]["start_time"].asString() + "\"";
			SQLstmt += ",\"" + (*match_data)[i]["end_time"].asString() + "\",";
			convertNumToString(&converter, (*match_data)[i]["worlds"][FIRST_SRV].asInt(),&SQLstmt);
			SQLstmt += ",";
			convertNumToString(&converter, (*match_data)[i]["worlds"][SECOND_SRV].asInt(),&SQLstmt);
			SQLstmt += ",";
			convertNumToString(&converter, (*match_data)[i]["worlds"][THIRD_SRV].asInt(),&SQLstmt);
			get_server_populations((*match_data)[i]["worlds"][FIRST_SRV].asInt(),(*match_data)[i]["worlds"][SECOND_SRV].asInt(),(*match_data)[i]["worlds"][THIRD_SRV].asInt(),&SQLstmt);
			SQLstmt += ");";
			//
			try
			{
				stmt = con->createStatement();
				stmt->execute(SQLstmt);
			}
			catch (sql::SQLException &e)
			{
				cout << e.what() << endl;
			}
		}
	}
	delete stmt;
}
void get_server_ppt(const Json::Value *match_data, int mapNum, string *SQLstmt, stringstream *converter, sql::Connection *con)
{
	sql::Statement *stmt;
	sql::ResultSet *res; //TODO look at optimizing this loop; it queries the DB a lot
	stmt = con->createStatement();
	int green_ppt = 0, blue_ppt = 0, red_ppt = 0;
	for (int i = 0; i < (int)(*match_data)["maps"][mapNum]["objectives"].size(); i++)
	{
		try
		{
			res = stmt->executeQuery("SELECT ppt_value FROM objective WHERE obj_id = \"" + (*match_data)["maps"][mapNum]["objectives"][i]["id"].asString() + "\";");
			res->next();
			if ((*match_data)["maps"][mapNum]["objectives"][i]["owner"].asString() == "Green")
			{
				green_ppt += res->getInt("ppt_value");
			}
			else if ((*match_data)["maps"][mapNum]["objectives"][i]["owner"].asString() == "Blue")
			{
				blue_ppt += res->getInt("ppt_value");
			}
			else if ((*match_data)["maps"][mapNum]["objectives"][i]["owner"].asString() == "Red")
			{
				red_ppt += res->getInt("ppt_value");
			}
			delete res;
		}
		catch (sql::SQLException &e)
		{
			cout << e.what() << endl;	
		}
	}
	(*SQLstmt) += ",";
	convertNumToString(converter,green_ppt,SQLstmt);
	(*SQLstmt) += ",";
	convertNumToString(converter,blue_ppt,SQLstmt);
	(*SQLstmt) += ",";
	convertNumToString(converter,red_ppt,SQLstmt);
	delete stmt;
}
/* */
void append_server_stats(string data, string *SQLstmt)
{
	if (data == "")
	{
		data = "0";
	}
	(*SQLstmt) += data;
}
void store_mapScores(const Json::Value *match_data, int mapNum, sql::Connection *con)
{
	stringstream converter;
	sql::Statement *stmt;
	stmt = con->createStatement();
	bool errorCorrected = false;
	time_t t = time(NULL); //get current local time
    struct tm * UTCTime = gmtime( & t ); //convert current time to UTC
    //
    string SQLstmt = "INSERT INTO map_scores VALUES(\"";
    convertNumToString(&converter,((*UTCTime).tm_year + 1900),&SQLstmt);
    SQLstmt += "-";
    convertNumToString(&converter,((*UTCTime).tm_mon + 1),&SQLstmt);
    SQLstmt += "-";
    convertNumToString(&converter,((*UTCTime).tm_mday),&SQLstmt);
    SQLstmt += " ";
    convertNumToString(&converter,((*UTCTime).tm_hour),&SQLstmt);
    SQLstmt += ":";
    convertNumToString(&converter,((*UTCTime).tm_min),&SQLstmt);
    SQLstmt += ":";
    convertNumToString(&converter,((*UTCTime).tm_sec),&SQLstmt);
    SQLstmt += "\",\"" + (*match_data)["id"].asString() + "\"";
    SQLstmt += ",\"" + (*match_data)["start_time"].asString() + "\"";
    SQLstmt += ",\"" + (*match_data)["maps"][mapNum]["type"].asString() + "\",";
    convertNumToString(&converter,((*match_data)["maps"][mapNum]["scores"][FIRST_SRV].asInt()),&SQLstmt);
    SQLstmt += ",";
    convertNumToString(&converter,((*match_data)["maps"][mapNum]["scores"][SECOND_SRV].asInt()),&SQLstmt);
    SQLstmt += ",";
    convertNumToString(&converter,((*match_data)["maps"][mapNum]["scores"][THIRD_SRV].asInt()),&SQLstmt);
    SQLstmt += ",";
    //
    if (((*match_data)["maps"][mapNum]["kills"][FIRST_SRV].asInt()) == 0
    && ((*match_data)["maps"][mapNum]["kills"][SECOND_SRV].asInt()) == 0
    && ((*match_data)["maps"][mapNum]["kills"][THIRD_SRV].asInt()) == 0)
    { /* Error correction for API-issues */
    	sql::ResultSet *res;
    	string start_time = (*match_data)["start_time"].asString();
    	start_time[10] = ' '; //manually reformatting the time-string from the API format to a mySQL format
    	start_time.erase(19,1); // ^^
		res = stmt->executeQuery("SELECT timeStamp, "FIRST_SRV"Kills, "SECOND_SRV"Kills, "THIRD_SRV"Kills, "FIRST_SRV"Deaths, "SECOND_SRV"Deaths, "THIRD_SRV"Deaths FROM map_scores WHERE match_id = \"" + (*match_data)["id"].asString() + "\" and map_id = \"" + (*match_data)["maps"][mapNum]["type"].asString() + "\" and start_time = \"" + start_time + "\" ORDER BY timeStamp DESC LIMIT 1;");
		if (res->next())
		{
			append_server_stats(res->getString(FIRST_SRV"Kills"),&SQLstmt);
			SQLstmt += ",";
			append_server_stats(res->getString(SECOND_SRV"Kills"),&SQLstmt);
			SQLstmt += ",";
			append_server_stats(res->getString(THIRD_SRV"Kills"),&SQLstmt);
			SQLstmt += ",";
			append_server_stats(res->getString(FIRST_SRV"Deaths"),&SQLstmt);
			SQLstmt += ",";
			append_server_stats(res->getString(SECOND_SRV"Deaths"),&SQLstmt);
			SQLstmt += ",";
			append_server_stats(res->getString(THIRD_SRV"Deaths"),&SQLstmt);
		}
		else
		{
			SQLstmt += "0,0,0,0,0,0";
		}
		errorCorrected = true;
		delete res;
    }
    else
    {
		convertNumToString(&converter,((*match_data)["maps"][mapNum]["kills"][FIRST_SRV].asInt()),&SQLstmt);
		SQLstmt += ",";
		convertNumToString(&converter,((*match_data)["maps"][mapNum]["kills"][SECOND_SRV].asInt()),&SQLstmt);
		SQLstmt += ",";
		convertNumToString(&converter,((*match_data)["maps"][mapNum]["kills"][THIRD_SRV].asInt()),&SQLstmt);
		SQLstmt += ",";
		convertNumToString(&converter,((*match_data)["maps"][mapNum]["deaths"][FIRST_SRV].asInt()),&SQLstmt);
		SQLstmt += ",";
		convertNumToString(&converter,((*match_data)["maps"][mapNum]["deaths"][SECOND_SRV].asInt()),&SQLstmt);
		SQLstmt += ",";
		convertNumToString(&converter,((*match_data)["maps"][mapNum]["deaths"][THIRD_SRV].asInt()),&SQLstmt);
    }
    get_server_ppt(match_data, mapNum, &SQLstmt, &converter, con);
	if (errorCorrected == true)
    {
   		SQLstmt += ",1";
    }
    else
    {
    	SQLstmt += ",0";
    }
    SQLstmt += ");";
    try
	{
		stmt->execute(SQLstmt);
	}
	catch (sql::SQLException &e)
	{
		cout << e.what() << endl;
	}
	delete stmt;
}
/* */
void get_matchDetails(string region, sql::Connection *con, double ingame_clock_time)
{ //region: 1 = NA, 2 = EU
	Easy request;
	stringstream matchDetails;
	Json::Value all_match_data;
	Json::Reader parser;
	/* */
	request.setOpt(cURLpp::Options::WriteStream(&matchDetails));
	request.setOpt(Url("https://api.guildwars2.com/v2/wvw/matches?ids=all"));
	try
	{
		request.perform();
		/* */
		if (parser.parse(matchDetails.str(), all_match_data))
		{
			if (!stored_matchDetails)
			{
				store_matchDetails(&all_match_data, region, con);
				for (int k = 0; k < (int)all_match_data.size(); k++)
				{
					if ((all_match_data[k]["id"].asString())[0] == region[0]) //filter down to matches in the region's range
					{
						previous_start_time = (all_match_data[k]["start_time"].asString());
						break;
					}
				}
				stored_matchDetails = true;
			}
			for (int j = 0; j < (int)all_match_data.size(); j++)
			{
				if ((all_match_data[j]["id"].asString())[0] == region[0]) //filter down to matches in the region's range
				{
					for (int i = 0; i < (int)all_match_data[j]["maps"].size(); i++)
					{
						store_activityData(&all_match_data[j], i, con, ingame_clock_time);
						if (ingame_clock_time == 14)
						{ //only store mapscore data every point-tally in-game
							store_mapScores(&all_match_data[j], i, con);
						}
					}
				}
			}
		}
	}
	catch (exception &e)
	{
		force_resync = true;
		cout << e.what() << endl;
	}
}
void sync_to_ingame_clock(string region, bool resync) //1 = NA, 2 = EU
{
	Easy request;
	stringstream matchDetails;
	Json::Value score_data;
	Json::Reader parser;
	int previousScore = 9999999, currentScore = 0; //initialize to very high value
	/* */
	string match_url = "https://api.guildwars2.com/v2/wvw/matches/" + region + "-1";
	//obtain the first match from the specified region. An entire region's matches
		//share the same in-game clock
	request.setOpt(Url(match_url));
	request.setOpt(cURLpp::Options::WriteStream(&matchDetails));
	if (resync == true)
	{ //only do an initial-pause on a resync, to save the number of calls made to the API
		usleep(MICROSEC*0.75*TIME_RES); //wait 45 seconds to reduce the number of API calls made
	}
	while (1)
	{
		try
		{
			request.perform();
			if (parser.parse(matchDetails.str(), score_data))
			{
				cout << "Syncing ..." << endl;
				if (stored_matchDetails && score_data["start_time"].asString() != previous_start_time)
				{
					cout << "NEW MATCH!" << endl;
					stored_matchDetails = false;
				}
				currentScore = score_data["scores"][FIRST_SRV].asInt() + score_data["scores"][SECOND_SRV].asInt() + score_data["scores"][THIRD_SRV].asInt();
				if (currentScore >= (previousScore+635))
				{
					break;
				}
				previousScore = currentScore;
			}
			matchDetails.str("");
			matchDetails.clear();
		}
		catch (exception &e)
		{
			cout << e.what() << endl;
		}
		/* */
		usleep(MICROSEC*0.0833*TIME_RES); //sleep for 5 seconds
	}
	/* */
}
void collect_data(string region) //1 = North American, 2 = European
{
	try 
    {
    	sql::mysql::MySQL_Driver *driver;
		sql::Connection *con;
		sql::Statement *stmt;
		driver = sql::mysql::get_mysql_driver_instance();
		con = driver->connect(IPADDR, USERNAME, PASSWORD);
		stmt = con->createStatement();
		stmt->execute("USE " DATABASE);
		delete stmt;
		//
		time_t beginTime, endTime;
		double elapsed_msecs;
		double ingame_clock_time = 14.0*60.0;
		sync_to_ingame_clock(region,false);
    	while (1)
		{
			beginTime = time(0);
			cout << "Beginning " << ingame_clock_time/60.0 << endl;
			get_matchDetails(region, con, ingame_clock_time/60.0);
			cout << "Ending " << ingame_clock_time/60.0 << endl;
			endTime = time(0);
			elapsed_msecs = difftime(endTime, beginTime) * MICROSEC;
			if (ingame_clock_time/TIME_RES == 15)
			{
				sync_to_ingame_clock(region,true); //resync to in-game clock every cycle
				elapsed_msecs = MICROSEC*TIME_RES-1;
			}
			else if (ingame_clock_time/TIME_RES <= 1)
			{
				ingame_clock_time = 16*60.0; //16 minutes because 1 TIME_RES is subtracted later
			}
			ingame_clock_time -= TIME_RES;
			if (elapsed_msecs/MICROSEC > TIME_RES || force_resync)
			{
				cout << "Too much time elapsed! Resyncing" << endl;
				elapsed_msecs = TIME_RES*MICROSEC-1;
				ingame_clock_time = 14*60.0;
				sync_to_ingame_clock(region,false);
				force_resync = false;
			}
			cout << elapsed_msecs/MICROSEC << " seconds elapsed" << endl;
			cout << "Time to sleep: " << (double)(MICROSEC*TIME_RES - elapsed_msecs)/MICROSEC << endl;
			usleep((double)(MICROSEC*TIME_RES - elapsed_msecs));
		}
		delete con;
	}
	catch (RuntimeError & e)
	{
		cout << e.what() << endl;
	}
	catch (LogicError & e)
	{
		cout << e.what() << endl;
	}
}
int main (int argc, char *argv[])
{
	collect_data("1");
	return 0;
}
